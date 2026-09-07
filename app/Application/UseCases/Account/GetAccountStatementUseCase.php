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

namespace App\Application\UseCases\Account;

use App\Domain\Account\Repositories\AccountRepositoryInterface;
use App\Domain\Account\Repositories\TransactionRepositoryInterface;
use App\Domain\Account\Repositories\UserRepositoryInterface;
use InvalidArgumentException;

class GetAccountStatementUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private AccountRepositoryInterface $accountRepository,
        private TransactionRepositoryInterface $transactionRepository
    ) {
    }

    public function execute(string $userUuid): array
    {
        $user = $this->userRepository->findByUuid($userUuid);
        if (! $user) {
            throw new InvalidArgumentException('User not found.');
        }

        $account = $this->accountRepository->findByUserId($user->getId());
        if (! $account) {
            throw new InvalidArgumentException('Account not found.');
        }

        $transactions = $this->transactionRepository->findAllByAccountId($account->getId());

        $statement = [];
        foreach ($transactions as $tx) {
            $isPix = $tx->getType() === 'pix_transfer';
            $isOrigin = $tx->getOriginAccountId() === $account->getId();

            if ($isPix) {
                $type = $isOrigin ? 'pix_out' : 'pix_in';
            } else {
                $type = $tx->getType();
            }

            $data = [];
            if ($type === 'pix_out') {
                $recipientData = $tx->getPayload()['recipient'] ?? null;
                if (! $recipientData) {
                    $destAccount = $this->accountRepository->findById($tx->getDestinationAccountId());
                    $destUser = $destAccount ? $this->userRepository->findById($destAccount->getUserId()) : null;
                    $recipientData = [
                        'name' => $destUser?->getName()->getValue() ?? 'Unknown',
                        'cpf' => $destUser?->getCpf()->getMasked() ?? '***.***.***-**',
                        'account_number' => $destAccount?->getAccountNumber()->getValue() ?? '',
                    ];
                }

                $data = [
                    'recipient' => $recipientData,
                    'receipt' => $recipientData,
                ];
            } elseif ($type === 'pix_in') {
                $origAccount = $tx->getOriginAccountId() ? $this->accountRepository->findById($tx->getOriginAccountId()) : null;
                $origUser = $origAccount ? $this->userRepository->findById($origAccount->getUserId()) : null;
                $originData = [
                    'name' => $origUser?->getName()->getValue() ?? 'Unknown',
                    'cpf' => $origUser?->getCpf()->getMasked() ?? '***.***.***-**',
                    'account_number' => $origAccount?->getAccountNumber()->getValue() ?? '',
                ];

                $data = [
                    'origin' => $originData,
                ];
            } elseif ($type === 'deposit') {
                $data = [
                    'account_number' => $account->getAccountNumber()->getValue(),
                ];
            }

            $statement[] = [
                'identifier' => $tx->getUuid(),
                'type' => $type,
                'amount' => $tx->getAmount()->getAmount(),
                'status' => $tx->getStatus(),
                'data' => $data,
                'date' => $tx->getCreatedAt()->format(DATE_ATOM),
            ];
        }

        return $statement;
    }
}
