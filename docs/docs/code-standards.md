---
sidebar_position: 2
---

# Code Standards

---

:::info
All rules will be applied in git pre-commit and GitHub workflows
:::

---

### PHPCS Rules
We have a project where we set ours standards and you can find then following the link below:   
[Leroy Merlin Code Standards](https://github.com/leroy-merlin-br/php-coding-standard)

---

### Rector Rules

#### **ACTION_INJECTION_TO_CONSTRUCTOR_INJECTION**
 > Converts action method injection to constructor injection. This promotes better design by centralizing dependency injection in the constructor, improving testability and dependency management.

#### **DEAD_CODE**
> Removes unused or obsolete code from the project. This keeps the codebase clean, reducing complexity and improving readability.

#### **PSR_4**
> Ensures that the code follows PSR-4 autoloading standards, where namespaces match the directory and file structure. This improves organization and efficient class loading.

#### **TYPE_DECLARATION**
> Adds type declarations for function parameters, return types, and variables wherever possible. It enhances code safety by defining expected types, reducing runtime errors.

#### **EARLY_RETURN**
> Refactors control structures like `if-else` into early returns. This simplifies the code by reducing deep nesting, making it easier to read.

#### **UP_TO_PHP_80**
> Applies updates to use new features and improvements available in PHP 8.0, ensuring the code is optimized for the latest version.

