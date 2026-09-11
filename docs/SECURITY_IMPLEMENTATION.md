# Sécurité implmentee - EcoRide

**Date :** 11 septembre 2026  
**Framework :** Symfony 8.1.6  
**PHP :** 8.4.25

---

## Vue d'ensemble

Ce document décrit les mécanismes de sécurité mis en place dans l'application EcoRide pour gérer l'authentification, l'autorisation et la suspension de comptes.

---

## 1. Authentification des utilisateurs

### Configuration

**Fichier :** `config/packages/security.yaml`

```yaml
security:
    password_hashers:
        App\Entity\User: 'auto'

    providers:
        app_user_provider:
            entity:
                class: App\Entity\User
                property: email

    firewalls:
        main:
            lazy: true
            provider: app_user_provider
            user_checker: App\Security\UserChecker
            form_login:
                login_path: app_login
                check_path: app_login
                enable_csrf: true
                default_target_path: app_home
            logout:
                path: app_logout
```

### Fonctionnement

- **Provider** : charge les utilisateurs depuis la table `user` via leur email.
- **Password hasher** : hachage automatique des mots de passe (algorithme optimal selon Symfony).
- **CSRF** : protection activee contre les attaques par rejeu de formulaire.
- **Logout** : route `/logout` configur�e pour invalider la session.

---

## 2. Contrôle de suspension de compte (UserChecker)

### Fichier : `src/Security/UserChecker.php`

```php
<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        if (!$user->isActive()) {
            throw new CustomUserMessageAccountStatusException(
                'Votre compte est suspendu. Contactez l'administrateur.'
            );
        }
    }

    public function checkPostAuth(
        UserInterface $user,
        ?TokenInterface $token = null
    ): void {
        $this->checkPreAuth($user);
    }
}
```

### Fonctionnement

- **Avant authentification (`checkPreAuth`)** : vérifie que `isActive = true`.
- **Apr�s authentification (`checkPostAuth`)** : rev�rifie l'�tat du compte.
- **Exception lev�e** : `CustomUserMessageAccountStatusException` avec message personnalis�.
- **R�sultat** : un utilisateur avec `is_active = 0` ne peut **pas** se connecter.

---

## 3. Entit� User

### Fichier : `src/Entity/User.php`

**Propri�t� cl� :**

```php
#[ORM\Column]
private ?bool $isActive = true;
```

**M�thodes :**

```php
public function isActive(): ?bool
{
    return $this->isActive;
}

public function setIsActive(bool $isActive): static
{
    $this->isActive = $isActive;

    return $this;
}
```

### R�le

- Champ `is_active` dans la table `user` (MySQL).
- Par d�faut � `true` lors de la cr�ation d'un utilisateur.
- Peut �tre modifi� manuellement (phpMyAdmin) ou via une interface admin (� venir).

---

## 4. Contr�le d'acc�s par r�les

### Configuration : `config/packages/security.yaml`

```yaml
access_control:
    - { path: ^/register, roles: PUBLIC_ACCESS }
    - { path: ^/login, roles: PUBLIC_ACCESS }
    - { path: ^/contact, roles: PUBLIC_ACCESS }
    - { path: ^/carpools, roles: PUBLIC_ACCESS }
    - { path: ^/employee, roles: ROLE_EMPLOYEE }
    - { path: ^/admin, roles: ROLE_ADMIN }
    - { path: ^/account, roles: ROLE_USER }
```

### R�les impl�ment�s

| R�le | Description | Pages accessibles |
|------|-------------|-------------------|
| `PUBLIC_ACCESS` | Aucun r�le requis | `/register`, `/login`, `/contact`, `/carpools` |
| `ROLE_USER` | Utilisateur authentifi� | `/account` |
| `ROLE_EMPLOYEE` | Employ� EcoRide | `/employee` |
| `ROLE_ADMIN` | Administrateur | `/admin` |

### Attribution des r�les

Les r�les sont stock�s dans la colonne `roles` (JSON) de la table `user`.

**Exemple :**

```json
["ROLE_ADMIN", "ROLE_USER"]
```

---

## 5. Fixtures de test

### Fichier : `src/DataFixtures/AppFixtures.php`

**Utilisateurs cr��s :**

| Email | R�les | isDriver | isPassenger | isActive | Mot de passe |
|-------|-------|----------|-------------|----------|--------------|
| `passager1@example.com` | `ROLE_USER` | false | true | true | `password123` |
| `chauffeur1@example.com` | `ROLE_USER` | true | false | true | `password123` |
| `mixte@example.com` | `ROLE_USER` | true | true | true | `password123` |
| `suspendu@example.com` | `ROLE_USER` | true | false | **false** | `password123` |
| `employee@ecoride.com` | `ROLE_EMPLOYEE`, `ROLE_USER` | false | false | true | `password123` |
| `admin@ecoride.com` | `ROLE_ADMIN`, `ROLE_USER` | false | false | true | `password123` |

### Commande

```bash
docker compose exec app php bin/console doctrine:fixtures:load --no-interaction
```

---

## 6. Tests de validation

### Test 1 : Connexion d'un compte actif

```text
Email : chauffeur1@example.com
Mot de passe : password123
R�sultat : ✓ Connexion r�ussie, redirection vers /
```

### Test 2 : Connexion d'un compte suspendu

```text
Email : suspendu@example.com
Mot de passe : password123
R�sultat : ✗ Message "Votre compte est suspendu. Contactez l'administrateur."
```

### Test 3 : Acc�s aux pages prot�g�es

```text
URL : /account
Utilisateur non connect� : → Redirection vers /login
Utilisateur connect� (ROLE_USER) : ✓ Acc�s autoris�

URL : /employee
Utilisateur ROLE_USER : → Acc�s refus� (403)
Utilisateur ROLE_EMPLOYEE : ✓ Acc�s autoris�

URL : /admin
Utilisateur ROLE_USER : → Acc�s refus� (403)
Utilisateur ROLE_ADMIN : ✓ Acc�s autoris�
```

---

## 7. Bonnes pratiques appliqu�es

- ✅ Hachage automatique des mots de passe (Symfony `password_hasher`).
- ✅ Protection CSRF sur le formulaire de connexion.
- ✅ S�paration authentification / autorisation.
- ✅ Contr�le d'�tat du compte avant et apr�s authentification.
- ✅ R�les granulaires pour l'acc�s aux pages.
- ✅ Fixtures reproductibles pour les tests.

---

## 8. Am�liorations pr�vues

- [ ] Interface admin pour suspendre/r�activer les comptes.
- [ ] Journalisation des tentatives de connexion �chou�es.
- [ ] R�cup�ration de mot de passe oubli�.
- [ ] Validation d'email � l'inscription (verify-email-bundle).
- [ ] Rate limiting sur `/login` (pr�vention brute-force).

---

## 9. R�f�rences

- [Documentation Symfony - Security](https://symfony.com/doc/current/security.html)
- [Documentation Symfony - User Checkers](https://symfony.com/doc/current/security/user_checkers.html)
- [Documentation Symfony - Access Control](https://symfony.com/doc/current/security/access_control.html)