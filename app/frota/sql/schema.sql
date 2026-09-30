CREATE DATABASE IF NOT EXISTS frota CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE frota;

-- ===== RBAC =====
CREATE TABLE papeis (
  id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(40) NOT NULL UNIQUE,
  descricao VARCHAR(150)
) ENGINE=InnoDB;
CREATE TABLE permissoes (
  id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(60) NOT NULL UNIQUE
) ENGINE=InnoDB;
CREATE TABLE papel_permissoes (
  papel_id TINYINT UNSIGNED NOT NULL,
  permissao_id SMALLINT UNSIGNED NOT NULL,
  PRIMARY KEY (papel_id, permissao_id),
  FOREIGN KEY (papel_id) REFERENCES papeis(id) ON DELETE CASCADE,
  FOREIGN KEY (permissao_id) REFERENCES permissoes(id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE TABLE usuarios (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(120) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  senha_hash VARCHAR(255) NOT NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  ultimo_login DATETIME NULL,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
CREATE TABLE usuario_papeis (
  usuario_id INT UNSIGNED NOT NULL,
  papel_id TINYINT UNSIGNED NOT NULL,
  PRIMARY KEY (usuario_id, papel_id),
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  FOREIGN KEY (papel_id) REFERENCES papeis(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ===== Acesso e auditoria (IP + local) =====
CREATE TABLE log_acesso (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT UNSIGNED NULL,
  email_tentado VARCHAR(150),
  ip VARCHAR(45) NOT NULL,
  local_aprox VARCHAR(150),
  user_agent VARCHAR(255),
  sucesso TINYINT(1) NOT NULL,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (ip, sucesso, criado_em)
) ENGINE=InnoDB;
CREATE TABLE auditoria (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT UNSIGNED NULL,
  ip VARCHAR(45),
  local_aprox VARCHAR(150),
  acao VARCHAR(20) NOT NULL,
  recurso VARCHAR(30) NOT NULL,
  registro_id INT UNSIGNED NULL,
  detalhe TEXT,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (recurso, registro_id)
) ENGINE=InnoDB;

-- ===== Domínio =====
CREATE TABLE veiculos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  placa CHAR(7) NOT NULL UNIQUE,
  renavam VARCHAR(11) NULL UNIQUE,
  chassi VARCHAR(17) NULL UNIQUE,
  marca VARCHAR(50) NOT NULL,
  modelo VARCHAR(60) NOT NULL,
  ano SMALLINT UNSIGNED NULL,
  cor VARCHAR(30) NULL,
  combustivel ENUM('gasolina','etanol','flex','diesel','eletrico','gnv','hibrido') NOT NULL DEFAULT 'flex',
  km_atual INT UNSIGNED NOT NULL DEFAULT 0,
  status ENUM('ativo','manutencao','inativo') NOT NULL DEFAULT 'ativo',
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE condutores (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(120) NOT NULL,
  cpf CHAR(11) NOT NULL UNIQUE,
  cnh_numero VARCHAR(11) NOT NULL UNIQUE,
  cnh_categoria ENUM('A','B','C','D','E','AB','AC','AD','AE') NOT NULL,
  cnh_validade DATE NOT NULL,
  telefone VARCHAR(11) NULL,
  email VARCHAR(150) NULL,
  status ENUM('ativo','inativo') NOT NULL DEFAULT 'ativo',
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE manutencoes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  veiculo_id INT UNSIGNED NOT NULL,
  tipo ENUM('preventiva','corretiva','revisao','troca_oleo','pneus','outros') NOT NULL,
  descricao TEXT NULL,
  data_manutencao DATE NOT NULL,
  km_manutencao INT UNSIGNED NULL,
  custo DECIMAL(10,2) NULL,
  oficina_nome VARCHAR(120) NULL,
  oficina_cnpj CHAR(14) NULL,
  proxima_data DATE NULL,
  proxima_km INT UNSIGNED NULL,
  status ENUM('agendada','concluida') NOT NULL DEFAULT 'concluida',
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (veiculo_id) REFERENCES veiculos(id),
  INDEX (veiculo_id, tipo, data_manutencao)
) ENGINE=InnoDB;

CREATE TABLE autorizacoes_conducao (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  veiculo_id INT UNSIGNED NOT NULL,
  condutor_id INT UNSIGNED NOT NULL,
  data_inicio DATE NOT NULL,
  data_fim DATE NOT NULL,
  finalidade VARCHAR(200) NOT NULL,
  destino VARCHAR(200) NULL,
  status ENUM('pendente','aprovada','negada','encerrada') NOT NULL DEFAULT 'pendente',
  aprovado_por INT UNSIGNED NULL,
  aprovado_em DATETIME NULL,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (veiculo_id) REFERENCES veiculos(id),
  FOREIGN KEY (condutor_id) REFERENCES condutores(id),
  FOREIGN KEY (aprovado_por) REFERENCES usuarios(id)
) ENGINE=InnoDB;

CREATE TABLE uso_veiculos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  veiculo_id INT UNSIGNED NOT NULL,
  condutor_id INT UNSIGNED NOT NULL,
  autorizacao_id INT UNSIGNED NULL,
  data_uso DATE NOT NULL,
  km_inicial INT UNSIGNED NOT NULL,
  km_final INT UNSIGNED NULL,
  observacao VARCHAR(255) NULL,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (veiculo_id) REFERENCES veiculos(id),
  FOREIGN KEY (condutor_id) REFERENCES condutores(id),
  FOREIGN KEY (autorizacao_id) REFERENCES autorizacoes_conducao(id),
  INDEX (veiculo_id, data_uso),
  CONSTRAINT chk_km CHECK (km_final IS NULL OR km_final >= km_inicial)
) ENGINE=InnoDB;

-- ===== Seeds RBAC =====
INSERT INTO permissoes (codigo)
SELECT CONCAT(r.n,'.',a.n)
FROM (SELECT 'veiculos' n UNION SELECT 'condutores' UNION SELECT 'manutencoes' UNION SELECT 'uso' UNION SELECT 'autorizacoes') r
CROSS JOIN (SELECT 'ver' n UNION SELECT 'criar' UNION SELECT 'editar' UNION SELECT 'excluir') a;
INSERT INTO permissoes (codigo) VALUES ('autorizacoes.aprovar'),('dashboard.ver'),('auditoria.ver'),('usuarios.gerenciar');

INSERT INTO papeis (nome, descricao) VALUES
 ('admin','Acesso total'),('gestor','Gestão da frota, sem administrar usuários'),
 ('operador','Registra uso e solicita autorizações'),('consulta','Somente leitura');

INSERT INTO papel_permissoes SELECT p.id, m.id FROM papeis p, permissoes m WHERE p.nome='admin';
INSERT INTO papel_permissoes SELECT p.id, m.id FROM papeis p, permissoes m WHERE p.nome='gestor' AND m.codigo<>'usuarios.gerenciar';
INSERT INTO papel_permissoes SELECT p.id, m.id FROM papeis p, permissoes m WHERE p.nome='operador'
  AND (m.codigo LIKE '%.ver' OR m.codigo IN ('uso.criar','uso.editar','autorizacoes.criar'));
INSERT INTO papel_permissoes SELECT p.id, m.id FROM papeis p, permissoes m WHERE p.nome='consulta' AND m.codigo LIKE '%.ver';

-- Usuário de aplicação com privilégio mínimo (troque a senha):
-- CREATE USER 'frota_app'@'localhost' IDENTIFIED BY 'TROCAR_SENHA';
-- GRANT SELECT,INSERT,UPDATE,DELETE ON frota.* TO 'frota_app'@'localhost';
