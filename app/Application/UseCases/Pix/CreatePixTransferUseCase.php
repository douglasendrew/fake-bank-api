<?php

declare(strict_types=1);

namespace App\Application\UseCases\Pix;

use App\Domain\Account\Entities\Transaction;
use App\Domain\Account\Repositories\AccountRepositoryInterface;
use App\Domain\Account\Repositories\PixKeyRepositoryInterface;
use App\Domain\Account\Repositories\TransactionRepositoryInterface;
use App\Domain\Account\Repositories\UserRepositoryInterface;
use App\Domain\Account\ValueObjects\Cpf;
use App\Domain\Account\ValueObjects\Money;
use InvalidArgumentException;

class CreatePixTransferUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private AccountRepositoryInterface $accountRepository,
        private PixKeyRepositoryInterface $pixKeyRepository,
        private TransactionRepositoryInterface $transactionRepository
    ) {}

    public function execute(string $senderUuid, string $targetKeyOrAccount, string $typeInput, mixed $amount): array
    {
        $type = strtolower(trim($typeInput));
        if (! in_array($type, ['email', 'cpf', 'account_number'], true)) {
            throw new InvalidArgumentException("Invalid PIX transfer type: {$typeInput}. Allowed types: 'email', 'cpf', 'account_number'.");
        }

        $money = Money::fromCents($amount);

        // Retrieve sender user and account
        $senderUser = $this->userRepository->findByUuid($senderUuid);
        if (! $senderUser) {
            throw new InvalidArgumentException('Sender user not found.');
        }

        $senderAccount = $this->accountRepository->findByUserId($senderUser->getId());
        if (! $senderAccount) {
            throw new InvalidArgumentException('Sender account not found.');
        }

        if ($senderAccount->getBalance()->getAmount() < $money->getAmount()) {
            throw new InvalidArgumentException('Insufficient funds for PIX transfer.');
        }

        // Find destination account
        $destinationAccount = null;
        if ($type === 'account_number') {
            $destinationAccount = $this->accountRepository->findByAccountNumber($targetKeyOrAccount);
        } else {
            $cleanKey = $targetKeyOrAccount;
            if ($type === 'cpf') {
                $cpf = new Cpf($targetKeyOrAccount);
                $cleanKey = $cpf->getUnformatted();
            }

            $pixKey = $this->pixKeyRepository->findByKey($cleanKey);
            if ($pixKey !== null) {
                $destinationAccount = $this->accountRepository->findById($pixKey->getAccountId());
            }
        }

        if (! $destinationAccount) {
            throw new InvalidArgumentException('Destination account or PIX key not found.');
        }

        if ($senderAccount->getId() === $destinationAccount->getId()) {
            throw new InvalidArgumentException('Cannot send PIX transfer to yourself.');
        }

        // Retrieve recipient user details
        $recipientUser = $this->userRepository->findById($destinationAccount->getUserId());

        $recipientData = [
            'name' => $recipientUser?->getName()->getValue() ?? 'Unknown',
            'cpf' => $recipientUser?->getCpf()->getMasked() ?? '***.***.***-**',
            'account_number' => $destinationAccount->getAccountNumber()->getValue(),
        ];

        // Create PIX transaction in 'created' status (awaiting confirmation)
        $transaction = new Transaction(
            originAccountId: $senderAccount->getId(),
            destinationAccountId: $destinationAccount->getId(),
            type: 'pix_transfer',
            amount: $money,
            status: 'created',
            payload: [
                'pix_type' => $type,
                'pix_key' => $targetKeyOrAccount,
                'transfer_type' => $type,
                'target_key_or_account' => $targetKeyOrAccount,
                'recipient' => $recipientData,
            ]
        );

        $savedTransaction = $this->transactionRepository->save($transaction);

        return [
            'message' => 'PIX transaction created. Please confirm to process transfer.',
            'identifier' => $savedTransaction->getUuid(),
            'amount' => $money->getAmount(),
            'status' => 'created',
            'recipient' => $recipientData,
        ];
    }
}
