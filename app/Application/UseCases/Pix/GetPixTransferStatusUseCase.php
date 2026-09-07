<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace App\Application\UseCases\Pix;

use App\Domain\Account\Repositories\AccountRepositoryInterface;
use App\Domain\Account\Repositories\TransactionRepositoryInterface;
use App\Domain\Account\Repositories\UserRepositoryInterface;
use InvalidArgumentException;

class GetPixTransferStatusUseCase
{
    public function __construct(
        private TransactionRepositoryInterface $transactionRepository,
        private AccountRepositoryInterface $accountRepository,
        private UserRepositoryInterface $userRepository
    ) {
    }

    public function execute(string $transactionUuid): array
    {
        $transaction = $this->transactionRepository->findByUuid($transactionUuid);

        if (! $transaction || $transaction->getType() !== 'pix_transfer') {
            throw new InvalidArgumentException('PIX transaction not found.');
        }

        $originAccount = $transaction->getOriginAccountId() ? $this->accountRepository->findById($transaction->getOriginAccountId()) : null;
        $destinationAccount = $this->accountRepository->findById($transaction->getDestinationAccountId());

        $originData = null;
        if ($originAccount) {
            $senderUser = $this->userRepository->findById($originAccount->getUserId());
            $originData = [
                'name' => $senderUser?->getName()->getValue() ?? 'Unknown',
                'cpf' => $senderUser?->getCpf()->getMasked() ?? '***.***.***-**',
                'account_number' => $originAccount->getAccountNumber()->getValue(),
            ];
        }

        $recipientData = $transaction->getPayload()['recipient'] ?? null;
        if (! $recipientData && $destinationAccount) {
            $recipientUser = $this->userRepository->findById($destinationAccount->getUserId());
            $recipientData = [
                'name' => $recipientUser?->getName()->getValue() ?? 'Unknown',
                'cpf' => $recipientUser?->getCpf()->getMasked() ?? '***.***.***-**',
                'account_number' => $destinationAccount->getAccountNumber()->getValue(),
            ];
        }

        return [
            'identifier' => $transaction->getUuid(),
            'type' => $transaction->getType(),
            'amount' => $transaction->getAmount()->getAmount(),
            'status' => $transaction->getStatus(),
            'origin' => $originData,
            'recipient' => $recipientData,
            'created_at' => $transaction->getCreatedAt()->format(DATE_ATOM),
            'updated_at' => $transaction->getUpdatedAt()->format(DATE_ATOM),
        ];
    }
}
