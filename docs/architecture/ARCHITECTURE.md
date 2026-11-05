# Architecture Rules — purpletree-v3

> **Purpose:** This is the authoritative, prescriptive rulebook for all feature work in this project.  
> Every AI agent, developer, and code reviewer must follow these rules without exception.  
> When a rule conflicts with existing code, the rule wins — existing code must be updated.

---

## Table of Contents

1. [Core Principles](#1-core-principles)
2. [Module Structure](#2-module-structure)
3. [Dependency Rule — What Can Import What](#3-dependency-rule--what-can-import-what)
4. [Layer Responsibilities](#4-layer-responsibilities)
   - 4.1 [Controllers](#41-controllers--http-layer)
   - 4.2 [Form Requests](#42-form-requests)
   - 4.3 [DTOs](#43-dtos--data-transfer-objects)
   - 4.4 [Use Cases](#44-use-cases--application-layer)
   - 4.5 [Repositories](#45-repositories--persistence-layer)
   - 4.6 [Services](#46-services--infrastructure-layer)
   - 4.7 [Models](#47-models--eloquent)
5. [Exception Handling](#5-exception-handling)
   - 5.1 [The Three Categories](#51-the-three-exception-categories)
   - 5.2 [DomainException Rules](#52-domainexception-rules)
   - 5.3 [ExternalServiceException Rules](#53-externalserviceexception-rules)
   - 5.4 [Global Handler](#54-global-exception-handler)
   - 5.5 [Forbidden Patterns](#55-forbidden-exception-patterns)
6. [File Storage — FileStorageService](#6-file-storage--filestorageservice)
7. [Cross-Module Communication](#7-cross-module-communication)
8. [Testing Rules](#8-testing-rules)
9. [Static Analysis — PHPStan](#9-static-analysis--phpstan)
10. [Naming Conventions](#10-naming-conventions)
11. [SOLID Applied to This Project](#11-solid-applied-to-this-project)
12. [New Feature Checklist](#12-new-feature-checklist)

---

## 1. Core Principles

This project follows **Clean Architecture** (Uncle Bob) adapted for a Laravel modular monolith.

The non-negotiable rules are:

1. **Dependencies point inward only.** Framework classes (`Request`, `Model`, `Storage`) are never imported into use cases or domain logic. Use cases import interfaces — never concrete implementations.
2. **Use cases own application logic.** Controllers validate and delegate. Services wrap infrastructure. Repositories abstract persistence. Nothing else.
3. **Every exception belongs to a category.** There are exactly three (see §5). No `catch (\Exception $e)` anywhere except in the global handler.
4. **Repositories are the only entry point to the database.** No `Model::where()`, `Model::find()`, or `->save()` outside a repository.
5. **DTOs are dumb.** A DTO holds data and transforms it. It never injects a service, calls the IoC container, or performs I/O.
6. **PHPStan level 7 is mandatory.** No `@phpstan-ignore`, no suppression. Fix the code.
7. **Use enums for finite domain values.** User types, statuses, types, badges, permissions, and other fixed sets must use backed enums. Never compare raw text such as `$user->user_type === 'customer'`; use `UserTypeEnum::Customer->value` (or the cast enum case) instead.

---

## 2. Module Structure

Every module must follow this exact folder layout. Do not deviate.

```
modules/{ModuleName}/
├── Contracts/                     ← Interfaces owned by this module
│   └── Repositories/
│       └── WorkExperienceRepositoryInterface.php
├── DTOs/                          ← Strictly split into Inputs and Outputs
│   ├── Inputs/                    ← Data coming IN from the frontend/caller
│   │   └── CreateWorkExperienceInput.php   ← Verb prefix: Create, Update, Delete, Complete...
│   └── Outputs/                   ← Data going OUT to the frontend/caller
│       └── WorkExperienceOutput.php        ← Noun: entity name + Output suffix
├── Enums/                         ← PHP 8.1 backed enums for this module's domain
│   └── WorkContractTypeEnum.php
├── Exceptions/                    ← Module-specific domain exceptions (extend DomainException)
│   └── WorkExperienceNotFoundException.php
├── Http/
│   ├── Controllers/
│   │   └── V1/
│   │       └── WorkExperiences/
│   │           └── CreateWorkExperienceController.php   ← Single action controllers only
│   └── Requests/
│       └── WorkExperienceRequest.php                    ← All validation here
├── Jobs/                          ← Queue jobs (if needed)
├── Listeners/                     ← Event listeners (if needed)
├── Models/                        ← Eloquent models scoped to this module (if not in Core)
├── Providers/
│   ├── {ModuleName}ServiceProvider.php                  ← Binds interfaces to implementations
│   └── RouteServiceProvider.php
├── Repositories/                  ← Concrete repository implementations
│   └── WorkExperienceRepository.php
├── Rules/                         ← Custom Laravel validation rules
├── UseCases/                      ← One class = one use case
│   └── WorkExperienceUseCase.php
└── routes/
    └── api.php
```

### Rules
- Shared models, base exceptions, and base contracts live in `modules/Core/`
- Module-specific exceptions live in `modules/{ModuleName}/Exceptions/`
- Module-specific repository interfaces live in `modules/{ModuleName}/Contracts/Repositories/`
- A module must **never** import from another module's `Http/`, `Models/`, or `Repositories/` namespace — only from its `Contracts/`

---

## 3. Dependency Rule — What Can Import What

```
┌──────────────────────────────────────────────────────────┐
│                    HTTP / Framework Layer                 │
│  Controller  →  FormRequest  →  DTO  →  UseCase          │
│       ↓                                                   │
│  (returns JsonResponse)                                   │
└───────────────────────────┬──────────────────────────────┘
                            │ depends on
┌───────────────────────────▼──────────────────────────────┐
│                   Application Layer                       │
│  UseCase  →  RepositoryInterface  →  ServiceInterface    │
│  UseCase throws DomainException                          │
└───────────────────────────┬──────────────────────────────┘
                            │ depends on (via interface)
┌───────────────────────────▼──────────────────────────────┐
│                Infrastructure / Persistence Layer         │
│  Repository (Eloquent)    Service (S3, HTTP, etc.)       │
│  Concrete implementations of interfaces                  │
└──────────────────────────────────────────────────────────┘
```

### Allowed imports per layer

| From \ To | Controller | UseCase | Repository | Service | Model | DTO | Exception |
|---|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| **Controller** | — | ✅ | ❌ | ❌ | ❌ | ✅ | ❌ |
| **UseCase** | ❌ | — | ✅ (interface) | ✅ (interface) | ❌ | ✅ | ✅ |
| **Repository** | ❌ | ❌ | — | ❌ | ✅ | ✅ | ✅ |
| **Service** | ❌ | ❌ | ❌ | ✅ | ❌ | ✅ | ✅ |
| **DTO** | ❌ | ❌ | ❌ | ❌ | ✅ (read only) | — | ❌ |

**Key rule:** Use cases must never import a concrete `Repository` or `Service` class. They import the **interface** declared in `Contracts/`.

---

## 4. Layer Responsibilities

### 4.1 Controllers — HTTP Layer

**File location:** `modules/{Module}/Http/Controllers/V1/{Feature}/{Action}Controller.php`  
**Base class:** `Modules\Core\Http\Controllers\BaseController`  
**Pattern:** Single action controller (`__invoke` only)

A controller has **exactly four responsibilities**:
1. Receive the validated `FormRequest`
2. Extract the authenticated user from `$request->user()`
3. Build the **Input DTO** from the validated Form Request
4. Call the use case and map the result to an **Output DTO** → return the response

**Nothing else.** No try/catch. No business logic. No service calls. No S3 uploads.

The full data flow through the controller is:
```
FormRequest (validated) → Input DTO → UseCase → Eloquent Model → Output DTO → JsonResponse
                                              ↘ Output DTO ────────────────→ JsonResponse
                                                 (composite case — no mapping needed)
```

```php
<?php

namespace Modules\Experience\Http\Controllers\V1\WorkExperiences;

use Illuminate\Http\JsonResponse;
use Modules\Core\Http\Controllers\BaseController;
use Modules\Core\Models\Common\User;
use Modules\Experience\DTOs\Inputs\CreateWorkExperienceInput;
use Modules\Experience\DTOs\Outputs\WorkExperienceOutput;
use Modules\Experience\Http\Requests\WorkExperienceRequest;
use Modules\Experience\UseCases\CreateWorkExperienceUseCase;

class CreateWorkExperienceController extends BaseController
{
    public function __invoke(
        WorkExperienceRequest $request,
        CreateWorkExperienceUseCase $useCase
    ): JsonResponse {
        /** @var User $user */
        $user  = $request->user();
        $input = CreateWorkExperienceInput::from($request->validated());
        $model = $useCase->execute($user, $input);

        return $this->makeSuccess('Work experience created', WorkExperienceOutput::fromModel($model));
    }
}
```

**Forbidden in controllers:**
- `try { ... } catch (\Exception $e)` — let the global handler take over
- `$this->s3Service->uploadFile(...)` — file upload is a use case concern
- `Model::find(...)` — any direct Eloquent query
- `app()` or `App::make()` — use constructor injection
- `response()->json([...])` with hardcoded error shapes — use `makeError()` from `BaseController`

---

### 4.2 Form Requests

**File location:** `modules/{Module}/Http/Requests/{Feature}Request.php`  
**Base class:** `Illuminate\Foundation\Http\FormRequest`

All validation lives here. Never call `$request->validate([...])` inline in a controller.

```php
<?php

namespace Modules\Experience\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WorkExperienceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization is handled by route middleware
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'job_role_id'   => ['required', 'integer', 'exists:tempdataselects,id'],
            'company_name'  => ['required', 'string', 'max:255'],
            'start_date'    => ['required', 'date_format:Y-m-d'],
            'end_date'      => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'is_current'    => ['sometimes', 'boolean'],
        ];
    }
}
```

---

### 4.3 DTOs — Data Transfer Objects

**Base class:** `Modules\Core\DTOs\BaseDto` (which extends `Spatie\LaravelData\Data`)

DTOs are split into two subfolders. **Every DTO must be in one of them — never in the root `DTOs/` folder.**

---

#### DTOs/Inputs/ — Data coming from the frontend to the use case

**File location:** `modules/{Module}/DTOs/Inputs/{Verb}{Entity}Input.php`  
**Naming:** starts with a **verb** — `Create`, `Update`, `Delete`, `Complete`, `Send`, `Upload`, `Verify`, etc.

An Input DTO replaces the raw `array $payload` that controllers currently pass to use cases. It gives the use case a typed, validated data contract.

```php
<?php

namespace Modules\Experience\DTOs\Inputs;

use Modules\Core\DTOs\BaseDto;

class CreateWorkExperienceInput extends BaseDto
{
    public function __construct(
        public readonly int     $job_role_id,
        public readonly string  $company_name,
        public readonly string  $start_date,
        public readonly ?string $end_date,
        public readonly ?string $job_description,
    ) {}
}
```

The controller builds it from the validated Form Request:
```php
$input = CreateWorkExperienceInput::from($request->validated());
$result = $useCase->execute($user, $input);
```

---

#### DTOs/Outputs/ — Data returned from the use case to the controller

**File location:** `modules/{Module}/DTOs/Outputs/{Entity}Output.php`  
**Naming:** entity noun + `Output` suffix — `WorkExperienceOutput`, `UserProfileOutput`, `WorkExperienceListOutput`

**When does the use case return an Output DTO vs an Eloquent model?**

| Return type | When to use |
|---|---|
| **Eloquent model** | Simple single-entity CRUD. The controller calls `Output::fromModel($model)`. |
| **Output DTO directly** | The use case assembles data from multiple sources (model + signed URL + related records). The controller passes it straight to the response. |

```php
<?php

namespace Modules\Experience\DTOs\Outputs;

use Modules\Core\DTOs\BaseDto;
use Modules\Core\Models\Recruitment\Employment;

class WorkExperienceOutput extends BaseDto
{
    public function __construct(
        public readonly int     $id,
        public readonly string  $company_name,
        public readonly ?string $start_date,
        public readonly ?string $end_date,
        public readonly ?object $job_role,
        public readonly ?string $job_description,
    ) {}

    public static function fromModel(Employment $model): self
    {
        return new self(
            id:              $model->id,
            company_name:    $model->company_name,
            start_date:      $model->start_date?->format('Y-m-d'),
            end_date:        $model->end_date?->format('Y-m-d'),
            job_role:        $model->job_role_id ? (object)[
                'id'   => $model->job_role_id,
                'name' => $model->jobRole?->Mansione,
            ] : null,
            job_description: $model->job_role_description,
        );
    }
}
```

**Example — use case returning a model (simple case):**
```php
// CreateWorkExperienceUseCase::execute() returns Employment
public function execute(User $user, CreateWorkExperienceInput $input): Employment
{
    return $this->repository->create([...]);
}

// Controller maps to Output DTO
$model  = $useCase->execute($user, $input);
return $this->makeSuccess('Created', WorkExperienceOutput::fromModel($model));
```

**Example — use case returning Output DTO (composite case):**
```php
// GetUserProfileUseCase::execute() assembles from multiple sources
public function execute(User $user): UserProfileOutput
{
    $avatar  = $this->fileStorage->getSignedUrl($user->avatar);
    $profile = $this->profileRepository->findByUser($user->id);

    return new UserProfileOutput(
        id:         $user->id,
        full_name:  $user->full_name,
        avatar_url: $avatar,
        ...
    );
}

// Controller has nothing to map — just passes through
return $this->makeSuccess('OK', $useCase->execute($user));
```

**Rules for all DTOs:**
- A DTO is a **plain data container** — no service injection, no I/O, no business logic
- Never call `App::make()` or any IoC container method inside a DTO
- All properties are `readonly`
- Input DTOs use `BaseDto::from($array)` for construction (Spatie handles it)
- Output DTOs use a `static fromModel()` factory
- A DTO is **never** reused as both input and output — they have different shapes and different lifecycles

---

### 4.4 Use Cases — Application Layer

**File location:** `modules/{Module}/UseCases/{Action}{Entity}UseCase.php`

**One class = one user action.** A use case has exactly **one public method: `execute()`**.

This mirrors the single-action controller pattern. Each HTTP endpoint has one controller, one use case.

```
POST   /work-experiences      →  CreateWorkExperienceController  →  CreateWorkExperienceUseCase
GET    /work-experiences/{id} →  GetWorkExperienceController     →  GetWorkExperienceUseCase
PUT    /work-experiences/{id} →  UpdateWorkExperienceController  →  UpdateWorkExperienceUseCase
DELETE /work-experiences/{id} →  DeleteWorkExperienceController  →  DeleteWorkExperienceUseCase
GET    /work-experiences      →  ListWorkExperiencesController   →  ListWorkExperiencesUseCase
```

**Why not one `WorkExperienceUseCase` with create/update/delete/list methods?**
- Each operation has different dependencies — list needs no write access, delete needs no DTO
- A multi-method use case violates SRP: one class changes for five reasons
- It is impossible to mock only the needed method when writing unit tests
- It hides complexity: a 300-line use case class is a red flag that is invisible until it is too late

```php
<?php

namespace Modules\Experience\UseCases;

use Modules\Core\Models\Common\User;
use Modules\Core\Models\Recruitment\Employment;
use Modules\Experience\Contracts\Repositories\WorkExperienceRepositoryInterface;
use Modules\Experience\DTOs\Inputs\CreateWorkExperienceInput;

class CreateWorkExperienceUseCase
{
    public function __construct(
        private readonly WorkExperienceRepositoryInterface $workExperienceRepository,
    ) {}

    public function execute(User $user, CreateWorkExperienceInput $input): Employment
    {
        return $this->workExperienceRepository->create([
            'user_id'      => $user->id,
            'job_role_id'  => $input->job_role_id,
            'company_name' => $input->company_name,
            'start_date'   => $input->start_date,
            'end_date'     => $input->end_date,
        ]);
    }
}
```

```php
<?php

namespace Modules\Experience\UseCases;

use Modules\Core\Models\Common\User;
use Modules\Core\Models\Recruitment\Employment;
use Modules\Experience\Contracts\Repositories\WorkExperienceRepositoryInterface;
use Modules\Experience\Exceptions\WorkExperienceNotFoundException;

class GetWorkExperienceUseCase
{
    public function __construct(
        private readonly WorkExperienceRepositoryInterface $workExperienceRepository,
    ) {}

    public function execute(User $user, int $id): Employment
    {
        $experience = $this->workExperienceRepository->findByUserIdAndId($user->id, $id);

        if ($experience === null) {
            throw new WorkExperienceNotFoundException();
        }

        return $experience;
    }
}
```

```php
<?php

namespace Modules\Experience\UseCases;

use Illuminate\Support\Collection;
use Modules\Core\Models\Common\User;
use Modules\Experience\Contracts\Repositories\WorkExperienceRepositoryInterface;

class ListWorkExperiencesUseCase
{
    public function __construct(
        private readonly WorkExperienceRepositoryInterface $workExperienceRepository,
    ) {}

    /** @return Collection<int, Employment> */
    public function execute(User $user): Collection
    {
        return $this->workExperienceRepository->getByUserId($user->id);
    }
}
```

**Rules for use cases:**
- **One public method only: `execute()`** — no `create()`, `update()`, `delete()`, `list()`, `get()`
- **Naming: `{Action}{Entity}UseCase`** — `CreateWorkExperienceUseCase`, `DeleteWorkExperienceUseCase`
- `execute()` accepts an **Input DTO** for write operations (not a raw `array`)
- `execute()` returns either an **Eloquent model** (simple CRUD) or an **Output DTO** (composite data)
- Constructor parameters are interfaces — never concrete repository or service classes
- `execute()` throws `DomainException` subclasses for expected business failures
- `execute()` **never** returns `null` to signal failure — it throws
- `execute()` **never** has a try/catch block
- No `->save()` calls — all persistence goes through the repository
- No `Model::where()` or `Model::create()` — all queries go through the repository
- No `Storage::disk()`, `S3Service`, or any infrastructure class injected directly

**Existing multi-method use cases (ARCH-009) must be split as part of the refactor. See CODE_REVIEW_REPORT.md.**

---

### 4.5 Repositories — Persistence Layer

**Interface location:** `modules/{Module}/Contracts/Repositories/{Name}RepositoryInterface.php`  
**Implementation location:** `modules/{Module}/Repositories/{Name}Repository.php`  
**Binding location:** `modules/{Module}/Providers/{Module}ServiceProvider.php`

```php
<?php

namespace Modules\Experience\Contracts\Repositories;

use Illuminate\Support\Collection;
use Modules\Core\Models\Common\User;
use Modules\Core\Models\Recruitment\Employment;

interface WorkExperienceRepositoryInterface
{
    /** @return Collection<int, Employment> */
    public function getByUserId(string $userId): Collection;

    public function findByUserIdAndId(string $userId, int $id): ?Employment;

    /** @param array<string, mixed> $data */
    public function create(array $data): Employment;

    /** @param array<string, mixed> $data */
    public function update(int $id, array $data): Employment;

    public function delete(int $id): bool;
}
```

```php
<?php

namespace Modules\Experience\Repositories;

use Illuminate\Support\Collection;
use Modules\Core\Models\Recruitment\Employment;
use Modules\Experience\Contracts\Repositories\WorkExperienceRepositoryInterface;
use Modules\Experience\Exceptions\WorkExperienceNotFoundException;

class WorkExperienceRepository implements WorkExperienceRepositoryInterface
{
    public function getByUserId(string $userId): Collection
    {
        return Employment::query()
            ->where('user_id', $userId)
            ->orderByDesc('start_date')
            ->get();
    }

    public function findByUserIdAndId(string $userId, int $id): ?Employment
    {
        return Employment::query()
            ->where('id', $id)
            ->where('user_id', $userId)
            ->first();
    }

    public function create(array $data): Employment
    {
        return Employment::create($data);
    }

    public function update(int $id, array $data): Employment
    {
        $model = Employment::findOrFail($id);
        $model->update($data);
        return $model->refresh();
    }

    public function delete(int $id): bool
    {
        return Employment::destroy($id) > 0;
    }
}
```

**Rules for repositories:**
- Only Eloquent queries. No HTTP calls, no S3, no external services.
- Return Eloquent model instances or typed collections — never raw arrays
- Return `null` only on "not found" — never on failure (throw instead)
- No business logic — a repository only reads and writes data
- All methods are typed with full return types (required by PHPStan level 7)
- Bind in `ServiceProvider`:

```php
// modules/Experience/Providers/ExperienceServiceProvider.php
public function register(): void
{
    $this->app->bind(
        \Modules\Experience\Contracts\Repositories\WorkExperienceRepositoryInterface::class,
        \Modules\Experience\Repositories\WorkExperienceRepository::class,
    );
}
```

---

### 4.6 Services — Infrastructure Layer

Services wrap **external systems**: HTTP APIs, S3, email senders, SMS gateways.

**Rules for services:**
- A service may only be injected into a use case (via an interface), into another service, or into `FileStorageService`
- A service **never** contains business logic — it translates infrastructure calls into domain types
- On HTTP failure, throw `ExternalServiceException::fromResponse()` — never return `null` or `false`
- On connection failure, throw `ExternalServiceException::fromConnectionError()` — never swallow
- A service is never injected into a Repository, DTO, or Controller

---

### 4.7 Models — Eloquent

Models live in `modules/Core/Models/` (shared) or `modules/{Module}/Models/` (module-local).

**Rules for models:**
- Declare `$fillable` or `$guarded` explicitly — never leave both empty
- Declare all `$casts` for date, enum, and JSON columns
- Define relationships with full return types: `public function user(): BelongsTo`
- No static query methods (e.g., `static function getByUserId()`) — those belong in a repository
- No business logic in models — no methods that call services or perform side effects
- No `$model->save()` calls from outside the model's own repository

```php
<?php

namespace Modules\Core\Models\Recruitment;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\Common\User;

class Employment extends Model
{
    protected $table = 'employment';

    protected $fillable = [
        'user_id', 'job_role_id', 'company_name',
        'start_date', 'end_date', 'contract_type',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

---

## 5. Exception Handling

### 5.1 The Three Exception Categories

There are exactly **three** exception categories in this project. Every `throw` statement must produce one of them.

| Category | Base Class | Purpose | Who catches it |
|---|---|---|---|
| **Domain** | `Modules\Core\Exceptions\DomainException` | Expected business rule violations | Global handler in `bootstrap/app.php` |
| **External Service** | `Modules\Core\Exceptions\ExternalServiceException` | External HTTP API failures | Global handler in `bootstrap/app.php` |
| **Unexpected** | PHP built-ins (`\RuntimeException`, `\InvalidArgumentException`, etc.) | Bugs, DB failures, misconfiguration | Laravel's default handler (HTTP 500) |

---

### 5.2 DomainException Rules

**Base class:** `Modules\Core\Exceptions\DomainException` (already exists)

The message, HTTP status code, and optional error code are **defined inside the exception class**. The caller never passes a message string.

```php
<?php

namespace Modules\Experience\Exceptions;

use Modules\Core\Exceptions\DomainException;
use Symfony\Component\HttpFoundation\Response;

final class WorkExperienceNotFoundException extends DomainException
{
    protected ?int $customErrorCode = 4041; // Unique per exception type

    public function __construct()
    {
        parent::__construct(
            message: 'The requested work experience was not found.',
            code: Response::HTTP_NOT_FOUND // 404
        );
    }
}
```

```php
<?php

namespace Modules\Register\Exceptions;

use Modules\Core\Exceptions\DomainException;
use Symfony\Component\HttpFoundation\Response;

final class UserAlreadyRegisteredException extends DomainException
{
    protected ?int $customErrorCode = 4091;

    public function __construct()
    {
        parent::__construct(
            message: 'An account with this tax code already exists.',
            code: Response::HTTP_CONFLICT // 409
        );
    }
}
```

**Rules:**
- Every domain exception is `final`
- Constructor takes **zero arguments** from the caller
- Message is human-readable and safe to send to the frontend
- `customErrorCode` is unique across the entire application (use a prefix per module: `401x` for Auth, `404x` for not-found, etc.)
- Never include raw SQL errors, stack traces, or internal field names in the message
- Throw from use cases, never from controllers or repositories

---

### 5.3 ExternalServiceException Rules

**Base class:** `Modules\Core\Exceptions\ExternalServiceException` (already exists)  
**Subclasses:** `ExternalServiceClientErrorException` (4xx), `ExternalServiceServerErrorException` (5xx)  
**Factory methods:** `fromResponse()`, `fromConnectionError()` (already implemented)

Every HTTP client call must check the response and throw on failure:

```php
<?php

namespace Modules\Core\Services;

use Illuminate\Support\Facades\Http;
use Modules\Core\Exceptions\ExternalServiceException;

class NotificationService
{
    private const SERVICE_NAME = 'NotificationApi';

    public function sendWelcomeEmail(string $email, string $name): void
    {
        $url = config('services.notification.url') . '/send';
        $payload = ['email' => $email, 'name' => $name];

        try {
            $response = Http::post($url, $payload);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw ExternalServiceException::fromConnectionError($url, $e->getMessage(), self::SERVICE_NAME);
        }

        if (! $response->successful()) {
            throw ExternalServiceException::fromResponse($url, $response, self::SERVICE_NAME, $payload);
        }
    }
}
```

**Rules:**
- Every `Http::` call is wrapped in a `try/catch` for `ConnectionException` only
- Every response is checked with `$response->successful()` or `$response->ok()`
- Never return `null`, `false`, or `['status' => false]` on failure — always throw
- Never catch `ExternalServiceException` in use cases or controllers — let it propagate

---

### 5.4 Global Exception Handler

**File:** `bootstrap/app.php` (already configured)

The global handler is the **only** place where exceptions are caught and converted to HTTP responses.

Three renderers are already registered:
- `DomainException` → structured JSON with `errors`, optional `code`, HTTP status from `$e->getCode()`
- `ExternalServiceException` → `503 Service Unavailable`, full context logged, no details to frontend
- `Throwable` → `500 Internal Server Error`, full context logged, stack trace never sent to frontend

Do not add exception rendering anywhere else.

---

### 5.5 Forbidden Exception Patterns

The following patterns are **banned** in this codebase:

```php
// ❌ BANNED — broad catch in a controller
try {
    $result = $this->useCase->execute($dto);
} catch (\Exception $e) {
    return response()->json(['message' => $e->getMessage()], 500);
}

// ❌ BANNED — swallowing an exception
try {
    $this->s3Service->deleteFile($path);
} catch (\Exception $e) {
    // silent
}

// ❌ BANNED — catching to return null/false
try {
    return $this->httpClient->post($url, $payload);
} catch (\Exception $e) {
    return null;
}

// ❌ BANNED — exception message defined outside the class
throw new UserAlreadyRegisteredException('This user already exists'); // message at call site

// ❌ BANNED — catching unexpected exceptions in a service
try {
    return DB::transaction(fn () => $this->repository->create($data));
} catch (\Exception $e) {
    Log::error($e->getMessage());
    return false;
}
```

```php
// ✅ CORRECT — no try/catch in controller, let it propagate
public function __invoke(WorkExperienceRequest $request, WorkExperienceUseCase $useCase): JsonResponse
{
    $experience = $useCase->create($request->user(), $request->validated());
    return $this->makeSuccess('Created', WorkExperienceDto::from($experience));
}

// ✅ CORRECT — throw domain exception from use case
public function create(User $user, array $payload): Employment
{
    if ($this->repository->existsDuplicate($user->id, $payload['start_date'])) {
        throw new DuplicateWorkExperienceException();
    }
    return $this->repository->create([...]);
}

// ✅ CORRECT — throw external service exception from service
if (! $response->successful()) {
    throw ExternalServiceException::fromResponse($url, $response, 'PlatformApi');
}
```

---

## 6. File Storage — FileStorageService

`S3Service` is a low-level infrastructure class. It must be used only through `FileStorageService`.

### The Coordinator Pattern

```
Controller → UseCase → FileStorageService ──→ S3Service       (upload/delete/sign)
                                          └──→ AssetsRepository (persist record)
```

**`FileStorageService` is the only class that:**
- Knows about both S3 and the `assets` DB table
- Coordinates "upload file + save record" as one atomic operation
- Resolves pre-signed URLs for display

```php
<?php

namespace Modules\Core\Services;

use Illuminate\Http\UploadedFile;
use Modules\Core\Models\Common\Asset;
use Modules\Core\Models\Common\User;
use Modules\Core\Models\Recruitment\Training;
use Modules\Core\Repositories\AssetsRepository;

class FileStorageService
{
    public function __construct(
        private readonly S3Service        $s3Service,
        private readonly AssetsRepository $assetsRepository,
    ) {}

    public function storeAvatar(User $user, UploadedFile $file): Asset
    {
        $result = $this->s3Service->uploadFile($file, 'avatars');
        return $this->assetsRepository->updateOrCreateAvatar($user, $result);
    }

    public function storeCertificate(User $user, Training $training, UploadedFile $file): Asset
    {
        $result = $this->s3Service->uploadFile($file, 'certificates');
        return $this->assetsRepository->updateOrCreateCertificate($user, $training, $result);
    }

    public function delete(Asset $asset): void
    {
        $this->s3Service->deleteFile($asset->path); // throws ExternalServiceException on failure
        $this->assetsRepository->delete($asset->id);
    }

    public function getSignedUrl(Asset $asset, int $minutes = 60): string
    {
        return $this->s3Service->getTemporaryUrl($asset->path, $minutes);
    }
}
```

### Injection Rules

| Class | Can inject FileStorageService? | Can inject S3Service directly? |
|---|:---:|:---:|
| Controller | ❌ | ❌ |
| UseCase | ✅ | ❌ |
| Repository | ❌ | ❌ |
| Service | ❌ | ✅ (only FileStorageService itself) |
| DTO | ❌ | ❌ |

### AssetsRepository Must Not Touch S3

```php
// ✅ CORRECT — repository only touches the DB
public function delete(int $id): bool
{
    return Asset::destroy($id) > 0;
    // S3 deletion is NOT the repository's job
    // The caller (FileStorageService) handles S3 then calls this
}
```

---

## 7. Cross-Module Communication

Modules must never import concrete classes from another module's internal namespace.

### Allowed
- Import from `Modules\Core\*` (shared kernel)
- Import from `Modules\{Other}\Contracts\*` (published interface)
- Fire a Laravel event and let other modules listen — zero coupling

### Forbidden
```php
// ❌ BANNED — Register module importing Experience's use case directly
use Modules\Experience\UseCases\WorkExperienceUseCase;

// ❌ BANNED — importing another module's model
use Modules\User\Models\User; // use Modules\Core\Models\Common\User instead
```

### Event-Driven Cross-Module Communication

```php
// Register module fires an event
event(new UserRegistrationFinalized($user));

// Experience module listens without depending on Register
class PopulateInitialExperiencesListener
{
    public function handle(UserRegistrationFinalized $event): void
    {
        // set up default experience records for the new user
    }
}
```

Events live in the **firing** module. Listeners live in the **consuming** module. Both are registered in the consuming module's `ServiceProvider`.

### Canonical Shared Classes (live in Core)
- User model: `Modules\Core\Models\Common\User` — the only User model
- Asset model: `Modules\Core\Models\Common\Asset`
- All base exceptions: `Modules\Core\Exceptions\*`
- All shared enums: `Modules\Core\Enums\*`
- Base controller: `Modules\Core\Http\Controllers\BaseController`
- Base DTO: `Modules\Core\DTOs\BaseDto`

---

## 8. Testing Rules

### Test Types Required

| Type | Location | Purpose |
|---|---|---|
| Feature (HTTP) | `tests/Feature/{Module}/` | Test the full HTTP stack: request → response |
| Unit | `tests/Unit/{Module}/` | Test a use case in isolation with mocked repositories |

### Feature Test Pattern

```php
<?php

namespace Tests\Feature\Experience;

use Modules\Core\Models\Common\User;
use Tests\TestCase;

class CreateWorkExperienceTest extends TestCase
{
    public function test_authenticated_user_can_create_work_experience(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v3/experience/work-experiences', [
                'job_role_id'  => 1,
                'company_name' => 'ACME Corp',
                'start_date'   => '2020-01-01',
            ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'success', 'message',
                'data' => ['id', 'company_name', 'start_date'],
            ]);
    }

    public function test_validation_rejects_missing_required_fields(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/v3/experience/work-experiences', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['job_role_id', 'company_name', 'start_date']);
    }

    public function test_domain_exception_returns_correct_http_status(): void
    {
        $user = User::factory()->create();

        // Attempt to delete a non-existent experience
        $this->actingAs($user)
            ->deleteJson('/api/v3/experience/work-experiences/99999')
            ->assertNotFound()
            ->assertJsonFragment(['success' => false]);
    }
}
```

### Unit Test Pattern (Use Case with Mocked Repository)

```php
<?php

namespace Tests\Unit\Experience;

use Mockery;
use Modules\Core\Models\Common\User;
use Modules\Core\Models\Recruitment\Employment;
use Modules\Experience\Contracts\Repositories\WorkExperienceRepositoryInterface;
use Modules\Experience\Exceptions\WorkExperienceNotFoundException;
use Modules\Experience\UseCases\WorkExperienceUseCase;
use Tests\TestCase;

class WorkExperienceUseCaseTest extends TestCase
{
    public function test_find_throws_when_not_found(): void
    {
        $repo = Mockery::mock(WorkExperienceRepositoryInterface::class);
        $repo->shouldReceive('findByUserIdAndId')->once()->andReturn(null);

        $useCase = new WorkExperienceUseCase($repo);
        $user = new User(['id' => 1]);

        $this->expectException(WorkExperienceNotFoundException::class);
        $useCase->findForUser($user, 999);
    }

    public function test_create_delegates_to_repository(): void
    {
        $repo = Mockery::mock(WorkExperienceRepositoryInterface::class);
        $repo->shouldReceive('create')->once()->andReturn(new Employment(['id' => 42]));

        $useCase = new WorkExperienceUseCase($repo);
        $user = new User(['id' => 1]);

        $result = $useCase->create($user, [
            'job_role_id'  => 10,
            'company_name' => 'ACME',
            'start_date'   => '2023-01-01',
        ]);

        $this->assertEquals(42, $result->id);
    }
}
```

### Rules
- **Every use case must have at least one unit test** covering each business rule branch
- **Every controller must have at least one feature test** covering the happy path and one covering validation failure
- Tests **never** access the real database unless it is a feature test with `RefreshDatabase`
- Unit tests **always** mock repository and service dependencies — never use real implementations

---

## 9. Static Analysis — PHPStan

**Level: 7** — non-negotiable. Configured in `phpstan.neon`.

```neon
includes:
    - vendor/larastan/larastan/extension.neon

parameters:
    level: 7
    paths:
        - app
        - modules
        - routes
        - tests
    excludePaths:
        - */vendor/*
        - */storage/*
        - */bootstrap/cache/*
```

### Rules
- **Never use `@phpstan-ignore`** — fix the underlying code
- **All methods must have full return types** — `void`, `string`, `?int`, `Collection<int, Employment>`, etc.
- **All constructor parameters must be typed** — no untyped `$service` properties
- **All array shapes used for returns must be documented** with `@return array{key: type}` or converted to a DTO
- Run before every pull request: `vendor/bin/phpstan analyse` must return zero errors

### Common Level 7 Error Patterns to Fix

| Error | Fix |
|---|---|
| `Method returns void but return value is used` | Add return type, fix logic |
| `Cannot call method on nullable` | Add null check or use `findOrFail()` |
| `Mixed type passed to typed parameter` | Add explicit cast or type the source |
| `Array does not contain key` | Guard with `array_key_exists()` or use a DTO |
| `Static method called on instance` | Fix the call |

---

## 10. Naming Conventions

| Thing | Convention | Example |
|---|---|---|
| Controller | `{Action}{Entity}Controller` | `CreateWorkExperienceController` |
| Use Case | `{Action}{Entity}UseCase` | `CreateWorkExperienceUseCase`, `DeleteWorkExperienceUseCase` |
| Repository Interface | `{Entity}RepositoryInterface` | `WorkExperienceRepositoryInterface` |
| Repository | `{Entity}Repository` | `WorkExperienceRepository` |
| Input DTO | `{Verb}{Entity}Input` | `CreateWorkExperienceInput`, `CompleteRegistrationInput` |
| Output DTO | `{Entity}Output` | `WorkExperienceOutput`, `UserProfileOutput`, `WorkExperienceListOutput` |
| Form Request | `{Action}{Entity}Request` | `CreateWorkExperienceRequest`, `UpdateWorkExperienceRequest` |
| Domain Exception | `{Description}Exception` | `WorkExperienceNotFoundException`, `DuplicateWorkExperienceException` |
| Enum | `{Entity}{Property}Enum` | `WorkContractTypeEnum`, `NominationStatusEnum` |
| Event | `{Entity}{PastTenseVerb}` | `UserRegistrationFinalized`, `WorkExperienceDeleted` |
| Listener | `{Action}On{Event}Listener` | `PopulateExperiencesOnUserRegistered` |
| Job | `{Verb}{Entity}Job` | `ProcessResumeParsingJob`, `SyncMirpleUserJob` |
| Service | `{ExternalSystem}Service` | `PlatformService`, `NotificationService` |
| Test class | `{Tested}Test` | `WorkExperienceUseCaseTest`, `CreateWorkExperienceTest` |

**Class names are always PascalCase. Methods are camelCase. No exceptions.**

---

## 11. SOLID Applied to This Project

### S — Single Responsibility

Each class does one thing:
- **Controller:** Translate HTTP request → use case → HTTP response
- **Use Case:** Orchestrate one user-facing operation
- **Repository:** Query/persist one aggregate root
- **Service:** Wrap one external system
- **`FileStorageService`:** Coordinate file upload + DB record — nothing else

### O — Open/Closed

- Use cases are **open for extension** (new use cases) but **closed for modification** (existing ones)
- When adding a new file type, create `FileStorageService::storeDocument()` — do not modify the upload flow of avatars
- Exception types are **open for extension** — add new `DomainException` subclasses freely, never modify the base class

### L — Liskov Substitution

- Every `RepositoryInterface` implementation must be substitutable with a mock in tests
- Never add methods to a concrete repository that are not in the interface — they will not be mockable

### I — Interface Segregation

- Repository interfaces should be small and focused
- If a use case only reads, the interface it receives should only declare read methods
- Do not force a `WorkExperienceRepositoryInterface` to implement write methods if the consuming use case is read-only

### D — Dependency Inversion

- Use cases depend on `RepositoryInterface`, not `Repository`
- Use cases depend on `ServiceInterface` (when needed), not concrete service classes
- Bindings are declared in module `ServiceProvider` — not in the class itself
- Never use `app()`, `App::make()`, or `resolve()` inside a use case, service, or repository

---

## 12. New Feature Checklist

When adding any new feature, follow this checklist in order. Do not skip steps.

### Step 1 — Define the exception(s)
```
modules/{Module}/Exceptions/{Name}Exception.php
```
- Extend `DomainException`
- Zero constructor parameters
- Message and HTTP code defined inside the class

### Step 2 — Define the repository interface
```
modules/{Module}/Contracts/Repositories/{Name}RepositoryInterface.php
```
- All required query/persist methods
- Full return types on every method
- PHPDoc `@param` / `@return` for collection generics

### Step 3 — Write the repository implementation
```
modules/{Module}/Repositories/{Name}Repository.php
```
- Implements the interface
- Eloquent only — no S3, no HTTP
- Bind interface → implementation in `ServiceProvider`

### Step 4 — Write the use case
```
modules/{Module}/UseCases/{Action}{Name}UseCase.php
```
- Name is `{Action}{Entity}UseCase` — e.g., `CreateWorkExperienceUseCase`
- **One public method only: `execute()`**
- Constructor injects the repository/service **interface**
- `execute()` throws domain exceptions on failure
- No try/catch
- All persistence through repository methods

### Step 5 — Write the Input DTO
```
modules/{Module}/DTOs/Inputs/{Verb}{Entity}Input.php
```
- Name starts with a **verb**: `Create`, `Update`, `Delete`, `Complete`, `Send`, `Upload`
- All properties `readonly`
- Extends `BaseDto` — Spatie handles `::from($array)` construction
- No service injection, no I/O

### Step 6 — Write the Output DTO
```
modules/{Module}/DTOs/Outputs/{Entity}Output.php
```
- Name is entity noun + `Output` suffix: `WorkExperienceOutput`, `UserProfileOutput`
- `static fromModel($model): self` factory method
- No service injection, no I/O
- For composite output (multiple sources), the use case builds it directly

### Step 7 — Write the Form Request
```
modules/{Module}/Http/Requests/{Action}{Entity}Request.php
```
- All validation rules here
- `authorize()` returns `true` (auth is on the route middleware)
- Name matches the action: `CreateWorkExperienceRequest`, `UpdateWorkExperienceRequest`

### Step 8 — Write the controller
```
modules/{Module}/Http/Controllers/V1/{Feature}/{Action}{Name}Controller.php
```
- Single action (`__invoke`)
- Extends `BaseController`
- Zero try/catch
- Calls use case, returns `makeSuccess()`

### Step 8 — Register the route
```
modules/{Module}/routes/api.php
```
- Use `jwt.auth` middleware
- Route versioned under `v3/`

### Step 9 — Write tests
```
tests/Unit/{Module}/{Name}UseCaseTest.php
tests/Feature/{Module}/{Action}{Name}Test.php
```
- Unit: mock repository, test business rules
- Feature: HTTP-level, test request → response

### Step 10 — Run static analysis
```bash
vendor/bin/phpstan analyse
```
Zero errors required before opening a pull request.

---

## Quick Reference Card

```
New business rule violation  →  new DomainException subclass (zero-arg constructor)
New external API call        →  throw ExternalServiceException::fromResponse()
New file upload              →  FileStorageService::store{Type}()
New data query               →  new method on RepositoryInterface + Repository
New controller               →  single __invoke, extends BaseController, no try/catch
New use case                 →  {Action}{Entity}UseCase, one public execute() method
New input DTO                →  DTOs/Inputs/{Verb}{Entity}Input.php, all readonly props
New output DTO               →  DTOs/Outputs/{Entity}Output.php, fromModel() factory
Cross-module side effect     →  fire a Laravel event
```

---

*Architecture rules version: 1.0*  
*Laravel version: 11.x*  
*PHP minimum version: 8.1*  
*Applies to: All modules — Auth, Core, Experience, Recruitment, Register, User*
