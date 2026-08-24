# Apollo Users

Sistema de perfis de usuário, diretório (radar), avatares e matchmaking para a plataforma Apollo.

## Recursos

### Perfis de Usuário

- Página de perfil em `/id/{username}` (NUNCA `/user/username`)
- Avatar e capa personalizados
- Bio, localização, redes sociais
- Configurações de privacidade (público/membros/privado)
- Proteção contra enumeração de autores

### Radar de Usuários

- Diretório de membros em `/radar`
- Filtros por nome, localização
- Grid responsivo com cards de usuário
- Paginação infinita

### Sistema de Matchmaking

- Ações: like, pass, superlike
- Detecção de match mútuo
- Lista de matches
- Hook para notificações

### Proteção de Autor

- Bloqueia `?author=X` enumeration
- Redireciona archives de autor para `/id/username`
- Remove autor do REST API para não-admins
- Oculta autor em feeds RSS
- Remove sitemap de autores

## Instalação

1. Faça upload da pasta `apollo-users` para `/wp-content/plugins/`
2. Ative o plugin no painel WordPress
3. As tabelas do banco serão criadas automaticamente

## Dependências

- **Apollo Core** (obrigatório)
- **Apollo Login** (recomendado)

## Estrutura de Arquivos

```
apollo-users/
├── apollo-users.php          # Main plugin file
├── composer.json
├── uninstall.php
├── includes/
│   ├── constants.php         # Constants & configs
│   └── functions.php         # Helper functions
├── src/
│   ├── Plugin.php            # Main singleton
│   ├── Activation.php        # Activation handler
│   ├── Deactivation.php      # Deactivation handler
│   ├── Components/
│   │   ├── ProfileHandler.php
│   │   ├── UserFields.php
│   │   └── AuthorProtection.php
│   └── API/
│       ├── UsersController.php
│       └── ProfileController.php
├── templates/
│   ├── single-profile.php
│   ├── user-radar.php
│   ├── edit-profile.php
│   ├── profile-private.php
│   └── profile-login-required.php
└── assets/
    ├── css/
    │   ├── profile.css
    │   ├── radar.css
    │   └── edit-profile.css
    └── js/
        └── profile.js
```

## Tabelas do Banco

### `{prefix}_apollo_user_fields`

Campos customizados de usuário.

### `{prefix}_apollo_profile_views`

Registro de visualizações de perfil.

### `{prefix}_apollo_matchmaking`

Sistema de matchmaking (likes, passes, superlikes).

## REST API Endpoints

### Usuários

- `GET /apollo/v1/users` - Lista usuários (radar)
- `GET /apollo/v1/users/me` - Usuário atual
- `PUT /apollo/v1/users/me` - Atualizar perfil
- `GET /apollo/v1/users/{username}` - Perfil público

### Perfil

- `POST /apollo/v1/profile/avatar` - Upload avatar
- `DELETE /apollo/v1/profile/avatar` - Remover avatar
- `POST /apollo/v1/profile/cover` - Upload capa
- `DELETE /apollo/v1/profile/cover` - Remover capa
- `GET /apollo/v1/profile/views` - Quem viu meu perfil
- `POST /apollo/v1/profile/match` - Ação de match
- `GET /apollo/v1/profile/matches` - Meus matches

## Hooks

### Actions

- `apollo_users_match_created` - Disparado quando há match mútuo

### Filters

- `apollo_users_fields` - Modificar campos de perfil
- `apollo_author_protection_allowed_routes` - Rotas permitidas

## Funções Helper

```php
// Obter URL do perfil
apollo_get_profile_url( $user );

// Obter avatar do usuário
apollo_get_user_avatar_url( $user_id, $size );

// Obter capa do usuário
apollo_get_user_cover_url( $user_id );

// Verificar se usuário existe
apollo_user_exists( $username );
```

## Changelog

### 1.0.0

- Versão inicial
- Perfis de usuário com `/id/{username}`
- Sistema de avatar e capa
- Radar de usuários
- Sistema de matchmaking
- Proteção de enumeração de autores
- REST API completa
