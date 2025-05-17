# Sul Agenda - Sistema de Gerenciamento de Serviços Técnicos

Sistema web para gerenciamento de agendamentos e serviços técnicos.

## Requisitos do Sistema

- PHP 7.4 ou superior
- MySQL 8.0 ou superior
- Servidor web Apache/Nginx
- Extensões PHP necessárias:
  - PDO MySQL
  - JSON
  - Session

## Guia de Instalação Detalhado

### 1. Preparação do Ambiente

Primeiro, certifique-se de que seu servidor atende aos requisitos mínimos. Você pode instalar as dependências necessárias usando o script de instalação fornecido:

```bash
sudo chmod +x install.sh
sudo ./install.sh
```

### 2. Configuração do Banco de Dados

1. Crie um banco de dados MySQL para o sistema:
```bash
mysql -u root -p
CREATE DATABASE sulagenda CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
exit;
```

2. Importe o esquema do banco de dados:
```bash
mysql -u seu_usuario -p sulagenda < database/schema.sql
```

3. Configure as credenciais do banco de dados:
   Abra o arquivo `includes/config.php` e atualize as seguintes constantes:
```php
define('DB_HOST', 'localhost');     // Endereço do servidor MySQL
define('DB_NAME', 'sulagenda');     // Nome do banco de dados
define('DB_USER', 'seu_usuario');   // Usuário do MySQL
define('DB_PASS', 'sua_senha');     // Senha do MySQL
```

### 3. Configuração do Servidor Web

#### Para Apache:

1. Certifique-se de que o mod_rewrite está habilitado:
```bash
sudo a2enmod rewrite
sudo service apache2 restart
```

2. O arquivo `.htaccess` será criado automaticamente durante a instalação

#### Para Nginx:

Adicione a seguinte configuração ao seu arquivo de site:

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

### 4. Instalação do Sistema

Execute o script de instalação PHP:

```bash
php install.php
```

Este script irá:
- Verificar os requisitos do sistema
- Criar as tabelas necessárias
- Configurar o usuário administrador padrão
- Criar diretórios necessários
- Definir permissões corretas

## Credenciais de Acesso Padrão

- Administrador:
  - Email: admin@sulagenda.com
  - Senha: password

**IMPORTANTE:** Altere a senha do administrador após o primeiro acesso!

## Estrutura do Projeto

```
├── api/                    # Endpoints da API
│   ├── auth/              # Autenticação
│   ├── schedules/         # Gerenciamento de agendamentos
│   └── users/             # Gerenciamento de usuários
├── assets/
│   ├── css/              # Arquivos CSS
│   ├── img/              # Imagens e ícones
│   └── js/               # Arquivos JavaScript
├── database/             # Esquema do banco de dados
├── includes/            # Arquivos de configuração
└── pages/              # Páginas da aplicação
```

## Funcionalidades

- Autenticação e autorização de usuários
- Gerenciamento de agendamentos de serviços técnicos
- Notificações em tempo real
- Controle de acesso baseado em funções
- Design responsivo
- Histórico de serviços
- Gerenciamento de clientes

## Segurança

1. Troque a senha do administrador após a primeira instalação
2. Configure corretamente as permissões dos arquivos:
```bash
sudo chown -R www-data:www-data /caminho/para/sulagenda
sudo find /caminho/para/sulagenda -type f -exec chmod 644 {} \;
sudo find /caminho/para/sulagenda -type d -exec chmod 755 {} \;
```

3. Mantenha o PHP e MySQL atualizados
4. Use HTTPS em produção
5. Faça backup regular do banco de dados

## Ambiente de Desenvolvimento

Para iniciar o servidor de desenvolvimento:

```bash
php -S localhost:8000
```

## Solução de Problemas

### Erro de Permissão
Se encontrar erros de permissão:
```bash
sudo chown -R www-data:www-data /caminho/para/sulagenda
sudo chmod -R 755 /caminho/para/sulagenda
```

### Erro de Conexão com Banco de Dados
1. Verifique se o MySQL está rodando:
```bash
sudo service mysql status
```
2. Confirme as credenciais em `includes/config.php`
3. Verifique se o usuário tem permissões adequadas:
```sql
GRANT ALL PRIVILEGES ON sulagenda.* TO 'seu_usuario'@'localhost';
FLUSH PRIVILEGES;
```

### Páginas Não Encontradas
1. Verifique se o mod_rewrite está habilitado
2. Confirme se o .htaccess está presente
3. Verifique as permissões do diretório

## Suporte

Para suporte técnico ou dúvidas:
- Email: suporte@sulagenda.com
- WhatsApp: (XX) XXXX-XXXX

## Licença

Todos os direitos reservados © Sul Agenda

---

Desenvolvido com ❤️ pela equipe Sul Agenda
