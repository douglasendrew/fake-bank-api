<?php

declare(strict_types=1);

namespace App\Application\UseCases\Pix;

use App\Application\Jobs\ProcessPixTransferJob;
use App\Domain\Account\Repositories\AccountRepositoryInterface;
use App\Domain\Account\Repositories\PixKeyRepositoryInterface;
use App\Domain\Account\Repositories\UserRepositoryInterface;
use App\Domain\Account\ValueObjects\Cpf;
use App\Domain\Account\ValueObjects\Money;
use Hyperf\AsyncQueue\Driver\DriverFactory;
use InvalidArgumentException;

class SendPixTransferUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private AccountRepositoryInterface $accountRepository,
        private PixKeyRepositoryInterface $pixKeyRepository,
        private DriverFactory $driverFactory
    ) {}

    public function execute(string $senderUuid, string $targetKeyOrAccount, string $typeInput, float $amount): array
    {
        $type = strtolower(trim($typeInput));
        if (! in_array($type, ['email', 'cpf', 'account_number'], true)) {
            throw new InvalidArgumentException("Invalid PIX transfer type: {$typeInput}. Allowed types: 'email', 'cpf', 'account_number'.");
        }

        $money = new Money($amount);

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

        // Queue PIX transaction
        $driver = $this->driverFactory->get('default');
        $driver->push(new ProcessPixTransferJob(
            senderAccountId: $senderAccount->getId(),
            destinationAccountId: $destinationAccount->getId(),
            amount: $money->getAmount(),
            type: $type,
            targetKeyOrAccount: $targetKeyOrAccount
        ));

        return [
            'message' => 'PIX transfer requested and queued for processing successfully.',
            'amount' => $money->getAmount(),
            'target' => $targetKeyOrAccount,
            'type' => $type,
            'status' => 'pending',
        ];
    }
}
