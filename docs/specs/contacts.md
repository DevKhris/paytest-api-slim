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