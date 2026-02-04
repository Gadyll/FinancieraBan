from typing import Any, Optional


class MyBankException(Exception):
    def __init__(self, message: str, details: Optional[Any] = None):
        self.message = message
        self.details = details
        super().__init__(self.message)


class NotFoundException(MyBankException):
    pass


class UnauthorizedException(MyBankException):
    pass


class ForbiddenException(MyBankException):
    pass


class BadRequestException(MyBankException):
    pass


class ConflictException(MyBankException):
    pass


class DatabaseException(MyBankException):
    pass


class ValidationException(MyBankException):
    pass
