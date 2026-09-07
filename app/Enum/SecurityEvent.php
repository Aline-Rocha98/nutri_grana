<?php

namespace App\Enum;

enum SecurityEvent: string
{
    case LoginSuccess = 'LOGIN_SUCCESS';
    case LoginFailed = 'LOGIN_FAILED';
    case PasswordChanged = 'PASSWORD_CHANGED';
    case TransactionCreated = 'TRANSACTION_CREATED';
    case TransactionUpdated = 'TRANSACTION_UPDATED';
    case TransactionDeleted = 'TRANSACTION_DELETED';
    case AccountDeleted = 'ACCOUNT_DELETED';
}
