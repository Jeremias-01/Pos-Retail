USE pos_retail;

INSERT INTO usuarios (
  rol_id,
  nombre,
  correo,
  usuario,
  password_hash,
  intentos_fallidos,
  bloqueado,
  estado
) VALUES (
  1,
  'Administrador',
  'admin@posretail.local',
  'admin',
  '$2y$10$bnxsbgShJiovMsuIJKFZl.PabzWFfyosAnNzAk2F.6oVLhs3NVibu',
  0,
  0,
  1
);
