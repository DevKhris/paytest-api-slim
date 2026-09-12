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
  "userId": "A8LSIWVLGZ1Q"
}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "contact": {
      "id": "cnt_abc123def456",
      "ownerId": "TFJOTJQEL4P3",
      "contactUserId": "A8LSIWVLGZ1Q",
      "contact": {
        "id": "A8LSIWVLGZ1Q",
        "name": "María García"
      },
      "createdAt": "2026-09-07T10:00:00Z"
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
        "id": "cnt_abc123def456",
        "ownerId": "TFJOTJQEL4P3",
        "contactUserId": "A8LSIWVLGZ1Q",
        "contact": {
          "id": "A8LSIWVLGZ1Q",
          "name": "María García"
        },
        "createdAt": "2026-09-07T10:00:00Z"
      },
      {
        "id": "cnt_bcd234efg567",
        "ownerId": "TFJOTJQEL4P3",
        "contactUserId": "B9MTXJWPMH2R",
        "contact": {
          "id": "B9MTXJWPMH2R",
          "name": "Carlos López"
        },
        "createdAt": "2026-09-07T11:00:00Z"
      }
    ],
    "count": 2
  }
}
```

## Reglas de Negocio
- Solo se retorna el Nombre e ID Único del contacto (se consulta desde users, no se almacena en contacts)
- No se puede agregar a sí mismo como contacto
- No se pueden agregar contactos duplicados
- Los contactos se vinculan mediante el ID único del usuario

## Modelo de Datos

### Contact
```typescript
{
  id: string           // VARCHAR(36), UUID
  ownerId: string     // VARCHAR(12), FK a User (propietario)
  contactUserId: string // VARCHAR(12), FK a User (contacto agregado)
  createdAt: Date
}
```

**Constraint:** `UNIQUE unique_contact (ownerId, contactUserId)`

**Nota:** El campo `contact.name` se obtiene mediante JOIN con `users`, NO se almacena en la tabla contacts.

## Índices

| Índice | Columna | Tipo |
|--------|---------|------|
| unique_contact | (ownerId, contactUserId) | UNIQUE |
| idx_contacts_owner | ownerId | INDEX |
| idx_contacts_contact | contactUserId | INDEX |
