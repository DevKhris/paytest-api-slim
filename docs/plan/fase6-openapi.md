# FASE 6: Documentación OpenAPI

## Objetivo
Crear especificación OpenAPI 3.0 para documentar todos los endpoints.

---

## 6.1 OpenAPI Spec (docs/openapi.yaml)

```yaml
openapi: 3.0.3
info:
  title: PayTest Backend API
  description: |
    Sistema de simulación de pagos para capacitación en Testing con Agentes e Herramientas de IA.
    
    ## Autenticación
    Todos los endpoints protegidos requieren el header `Authorization: Bearer <token>`.
  version: 1.0.0
  contact:
    name: PayTest Team
servers:
  - url: http://localhost:8080/api
    description: Servidor de desarrollo

tags:
  - name: Auth
    description: Autenticación y registro
  - name: Account
    description: Gestión de cuentas
  - name: Transactions
    description: Transacciones y transferencias
  - name: Contacts
    description: Gestión de contactos
  - name: Sessions
    description: Sesiones de usuario

paths:
  /auth/register:
    post:
      tags:
        - Auth
      summary: Registrar nuevo usuario
      description: Registra un nuevo usuario con cuenta y saldo inicial
      requestBody:
        required: true
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/RegisterRequest'
      responses:
        '200':
          description: Usuario registrado exitosamente
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/AuthResponse'
        '400':
          description: Código de sala inválido o ya usado
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/ErrorResponse'
        '422':
          description: Validación fallida
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/ErrorResponse'

  /auth/login:
    post:
      tags:
        - Auth
      summary: Iniciar sesión
      description: Autentica usuario y retorna token JWT
      requestBody:
        required: true
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/LoginRequest'
      responses:
        '200':
          description: Login exitoso
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/AuthResponse'
        '401':
          description: Credenciales inválidas
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/ErrorResponse'

  /auth/sala/{code}:
    get:
      tags:
        - Auth
      summary: Validar código de sala
      description: Verifica si un código de sala es válido y no ha sido usado
      parameters:
        - name: code
          in: path
          required: true
          schema:
            type: string
      responses:
        '200':
          description: Resultado de validación
          content:
            application/json:
              schema:
                type: object
                properties:
                  success:
                    type: boolean
                  data:
                    type: object
                    properties:
                      valid:
                        type: boolean

  /accounts/balance:
    get:
      tags:
        - Account
      summary: Obtener balance
      description: Retorna el balance actual de la cuenta del usuario
      security:
        - bearerAuth: []
      responses:
        '200':
          description: Balance recuperado exitosamente
          content:
            application/json:
              schema:
                type: object
                properties:
                  success:
                    type: boolean
                  data:
                    type: object
                    properties:
                      user_unique_id:
                        type: string
                      balance:
                        type: number
                        format: float
        '401':
          description: No autorizado
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/ErrorResponse'
        '404':
          description: Cuenta no encontrada
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/ErrorResponse'

  /transactions/send:
    post:
      tags:
        - Transactions
      summary: Enviar dinero
      description: Transfiere dinero a otro usuario
      security:
        - bearerAuth: []
      requestBody:
        required: true
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/SendMoneyRequest'
      responses:
        '200':
          description: Transferencia exitosa
          content:
            application/json:
              schema:
                type: object
                properties:
                  success:
                    type: boolean
                  data:
                    type: object
                    properties:
                      transaction:
                        $ref: '#/components/schemas/Transaction'
                      new_balance:
                        type: number
                        format: float
        '400':
          description: Fondos insuficientes o validación fallida
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/ErrorResponse'
        '404':
          description: Usuario destinatario no encontrado
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/ErrorResponse'

  /transactions/history:
    get:
      tags:
        - Transactions
      summary: Historial de transacciones
      description: Obtiene el historial de transacciones del usuario
      security:
        - bearerAuth: []
      parameters:
        - name: limit
          in: query
          required: false
          schema:
            type: integer
            default: 50
            maximum: 100
      responses:
        '200':
          description: Historial recuperado
          content:
            application/json:
              schema:
                type: object
                properties:
                  success:
                    type: boolean
                  data:
                    type: object
                    properties:
                      transactions:
                        type: array
                        items:
                          $ref: '#/components/schemas/Transaction'
                      count:
                        type: integer

  /contacts/add:
    post:
      tags:
        - Contacts
      summary: Agregar contacto
      description: Agrega un usuario a la lista de contactos
      security:
        - bearerAuth: []
      requestBody:
        required: true
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/AddContactRequest'
      responses:
        '200':
          description: Contacto agregado
          content:
            application/json:
              schema:
                type: object
                properties:
                  success:
                    type: boolean
                  data:
                    type: object
                    properties:
                      contact:
                        $ref: '#/components/schemas/ContactInfo'
        '404':
          description: Usuario a agregar no encontrado
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/ErrorResponse'
        '409':
          description: Contacto ya existe
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/ErrorResponse'

  /contacts/list:
    get:
      tags:
        - Contacts
      summary: Listar contactos
      description: Obtiene la lista de contactos del usuario
      security:
        - bearerAuth: []
      responses:
        '200':
          description: Contactos recuperados
          content:
            application/json:
              schema:
                type: object
                properties:
                  success:
                    type: boolean
                  data:
                    type: object
                    properties:
                      contacts:
                        type: array
                        items:
                          $ref: '#/components/schemas/ContactInfo'
                      count:
                        type: integer

  /sessions/info:
    get:
      tags:
        - Sessions
      summary: Info de sesión
      description: Obtiene información de la sesión actual
      security:
        - bearerAuth: []
      responses:
        '200':
          description: Información de sesión
          content:
            application/json:
              schema:
                type: object
                properties:
                  success:
                    type: boolean
                  data:
                    type: object
                    properties:
                      user_unique_id:
                        type: string
                      ip_address:
                        type: string
                        nullable: true
                      user_agent:
                        type: string
                        nullable: true
                      created_at:
                        type: string
                        format: date-time
                      expires_at:
                        type: string
                        format: date-time

  /sessions/logout:
    post:
      tags:
        - Sessions
      summary: Cerrar sesión
      description: Invalida el token de sesión actual
      security:
        - bearerAuth: []
      responses:
        '200':
          description: Sesión cerrada exitosamente
          content:
            application/json:
              schema:
                type: object
                properties:
                  success:
                    type: boolean
                  data:
                    type: object
                    properties:
                      message:
                        type: string

components:
  securitySchemes:
    bearerAuth:
      type: http
      scheme: bearer
      bearerFormat: JWT

  schemas:
    RegisterRequest:
      type: object
      required:
        - name
        - password
        - sala_code
      properties:
        name:
          type: string
          minLength: 2
          example: Juan Pérez
        password:
          type: string
          minLength: 6
          example: "secret123"
        sala_code:
          type: string
          example: "SALA001"

    LoginRequest:
      type: object
      required:
        - unique_id
        - password
      properties:
        unique_id:
          type: string
          example: "TFJOTJQEL4P3"
        password:
          type: string
          example: "secret123"

    SendMoneyRequest:
      type: object
      required:
        - to_user_unique_id
        - amount
        - idempotency_key
      properties:
        to_user_unique_id:
          type: string
          description: ID único del usuario destinatario
          example: "A8LSIWVLGZ1Q"
        amount:
          type: number
          format: float
          minimum: 0.01
          example: 100.00
        idempotency_key:
          type: string
          description: Key única para evitar duplicaciones
          example: "tx_abc123def456"

    AddContactRequest:
      type: object
      required:
        - contact_user_unique_id
      properties:
        contact_user_unique_id:
          type: string
          description: ID único del usuario a agregar como contacto
          example: "A8LSIWVLGZ1Q"

    AuthResponse:
      type: object
      properties:
        success:
          type: boolean
        data:
          type: object
          properties:
            token:
              type: string
            token_type:
              type: string
              example: "Bearer"
            expires_in:
              type: integer
              example: 3600
            user:
              type: object
              properties:
                unique_id:
                  type: string
                name:
                  type: string

    Transaction:
      type: object
      properties:
        id:
          type: integer
        idempotency_key:
          type: string
        account_user_unique_id:
          type: string
        counterpart_user_unique_id:
          type: string
          nullable: true
        type:
          type: string
          enum: [INCOME, SPEND, REQUEST]
        amount:
          type: number
          format: float
        description:
          type: string
          nullable: true
        created_at:
          type: string
          format: date-time

    ContactInfo:
      type: object
      properties:
        contact_user_unique_id:
          type: string
        contact_name:
          type: string

    ErrorResponse:
      type: object
      properties:
        success:
          type: boolean
          example: false
        error:
          type: string
          example: "Error message"
```

---

## 6.2 Specs de Entidades (docs/specs/)

### auth.md
```markdown
# Auth API Spec

## Endpoints

### POST /auth/register
Registra un nuevo usuario con cuenta y saldo inicial.

**Request:**
```json
{
  "name": "Juan Pérez",
  "password": "secret123",
  "sala_code": "SALA001"
}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "token_type": "Bearer",
    "expires_in": 3600,
    "user": {
      "unique_id": "TFJOTJQEL4P3",
      "name": "Juan Pérez"
    }
  }
}
```

**Errores:**
- `400`: Código de sala inválido o usado
- `422`: Validación de datos fallida

---

### POST /auth/login
Inicia sesión y retorna token JWT.

**Request:**
```json
{
  "unique_id": "TFJOTJQEL4P3",
  "password": "secret123"
}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "token_type": "Bearer",
    "expires_in": 3600,
    "user": {
      "unique_id": "TFJOTJQEL4P3",
      "name": "Juan Pérez"
    }
  }
}
```

**Errores:**
- `401`: Credenciales inválidas

---

### GET /auth/sala/{code}
Valida si un código de sala es válido.

**Response (200):**
```json
{
  "success": true,
  "data": {
    "valid": true
  }
}
```

## Reglas de Negocio
- El código de sala solo puede usarse una vez
- La contraseña debe tener al menos 6 caracteres
- El nombre debe tener al menos 2 caracteres
- Al registrarse, se crea automáticamente una cuenta con saldo inicial aleatorio (100-500)
```

### accounts.md
```markdown
# Account API Spec

## Endpoints

### GET /accounts/balance
Obtiene el balance actual de la cuenta del usuario autenticado.

**Headers:**
```
Authorization: Bearer <token>
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "user_unique_id": "TFJOTJQEL4P3",
    "balance": 342.50
  }
}
```

**Errores:**
- `401`: No autorizado (token inválido o expirado)
- `404`: Cuenta no encontrada

## Reglas de Negocio
- El balance se calcula dinámicamente desde el historial de transacciones
- Formula: `balance = SUM(INCOME) - SUM(SPEND)`
- No hay valores negativos posibles (validación en capa de servicio)
```

### transactions.md
```markdown
# Transaction API Spec

## Endpoints

### POST /transactions/send
Envía dinero a otro usuario.

**Headers:**
```
Authorization: Bearer <token>
```

**Request:**
```json
{
  "to_user_unique_id": "A8LSIWVLGZ1Q",
  "amount": 50.00,
  "idempotency_key": "tx_abc123def456"
}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "transaction": {
      "id": 1,
      "idempotency_key": "tx_abc123def456",
      "account_user_unique_id": "TFJOTJQEL4P3",
      "counterpart_user_unique_id": "A8LSIWVLGZ1Q",
      "type": "SPEND",
      "amount": 50.00,
      "description": "Transfer to A8LSIWVLGZ1Q",
      "created_at": "2026-09-07T10:30:00Z"
    },
    "new_balance": 292.50
  }
}
```

**Errores:**
- `400`: Fondos insuficientes, monto inválido, o transferencia a sí mismo
- `404`: Usuario destinatario no encontrado
- `422`: Validación de datos fallida

---

### GET /transactions/history
Obtiene el historial de transacciones.

**Headers:**
```
Authorization: Bearer <token>
```

**Query Parameters:**
- `limit` (opcional): Número de transacciones a retornar (default: 50, max: 100)

**Response (200):**
```json
{
  "success": true,
  "data": {
    "transactions": [
      {
        "id": 2,
        "idempotency_key": "initial_income_TFJOTJQEL4P3",
        "account_user_unique_id": "TFJOTJQEL4P3",
        "counterpart_user_unique_id": null,
        "type": "INCOME",
        "amount": 342.50,
        "description": "Saldo inicial de bienvenida",
        "created_at": "2026-09-07T10:00:00Z"
      }
    ],
    "count": 1
  }
}
```

## Reglas de Negocio
- **Idempotencia**: Si se envía la misma `idempotency_key`, retorna la transacción existente
- **No negativos**: El balance nunca puede ser negativo
- **No infinitos/NaN**: Se validan valores numéricos
- **Auto-ingreso**: El destinatario recibe automáticamente una transacción INCOME
```

### contacts.md
```markdown
# Contact API Spec

## Endpoints

### POST /contacts/add
Agrega un usuario a la lista de contactos.

**Headers:**
```
Authorization: Bearer <token>
```

**Request:**
```json
{
  "contact_user_unique_id": "A8LSIWVLGZ1Q"
}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "contact": {
      "contact_user_unique_id": "A8LSIWVLGZ1Q",
      "contact_name": "María García"
    }
  }
}
```

**Errores:**
- `404`: Usuario a agregar no encontrado
- `409`: Contacto ya existe
- `422`: No puedes agregarte a ti mismo como contacto

---

### GET /contacts/list
Lista todos los contactos del usuario.

**Headers:**
```
Authorization: Bearer <token>
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "contacts": [
      {
        "contact_user_unique_id": "A8LSIWVLGZ1Q",
        "contact_name": "María García"
      },
      {
        "contact_user_unique_id": "B9MTXJWPMH2R",
        "contact_name": "Carlos López"
      }
    ],
    "count": 2
  }
}
```

## Reglas de Negocio
- Solo se retorna el Nombre e ID Único del contacto (no datos sensibles)
- No se puede agregar a sí mismo como contacto
- No se pueden agregar contactos duplicados
```

### sessions.md
```markdown
# Session API Spec

## Endpoints

### GET /sessions/info
Obtiene información de la sesión actual.

**Headers:**
```
Authorization: Bearer <token>
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "user_unique_id": "TFJOTJQEL4P3",
    "ip_address": "192.168.1.100",
    "user_agent": "Mozilla/5.0...",
    "created_at": "2026-09-07T10:00:00Z",
    "expires_at": "2026-09-07T11:00:00Z"
  }
}
```

**Errores:**
- `401`: Sesión no encontrada o expirada

---

### POST /sessions/logout
Invalida el token de sesión actual.

**Headers:**
```
Authorization: Bearer <token>
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "message": "Logged out successfully"
  }
}
```

## Reglas de Negocio
- El token JWT tiene vigencia de 1 hora (configurable via JWT_EXPIRES_IN)
- IP y User-Agent se almacenan para auditoría
- Sesiones expiradas son limpiadas automáticamente
```
