# MeetRooms — Documentation des Tests

## Résultats actuels

| Métrique | Valeur |
|---|---|
| Tests total | 15 |
| Réussis | 15 (100 %) |
| Assertions | 24 |
| Temps d'exécution | ~27 ms |
| Mémoire | 8 Mo |

---

## Architecture

```
tests/
├── bootstrap.php                        # Autoload et initialisation
├── TestHelper.php                       # Utilitaires partagés
├── ReservationConflictTest.php          # Tests de détection de conflits
└── Unit/
    └── HashTest.php                     # Tests unitaires purs (14 tests)
```

> Le dossier `tests/Integration/` contient `SQLiteTestCase.php`, base prête pour les tests d'intégration nécessitant une base de données.

---

## Lancer les tests

```bash
# Tous les tests
./vendor/bin/phpunit tests/

# Mode strict (équivalent recette/prod)
./vendor/bin/phpunit --fail-on-warning --fail-on-risky tests/

# Suite spécifique
./vendor/bin/phpunit tests/Unit/HashTest.php
./vendor/bin/phpunit tests/ReservationConflictTest.php

# Avec détail des tests incomplets ou ignorés
./vendor/bin/phpunit tests/ --display-incomplete --display-skipped
```

---

## Suites implémentées

### Hash Utility — 14 tests (`tests/Unit/HashTest.php`)

Tests unitaires purs, sans base de données. Couvrent `App\Utility\Hash` :

| Test | Description |
|---|---|
| `testGenerateCreatesSHA256Hash` | Format de sortie SHA256 |
| `testGenerateProducesConsistentResults` | Déterminisme du hash |
| `testGenerateProducesDifferentResultsForDifferentInputs` | Sensibilité à l'entrée |
| `testGenerateWithEmptySalt` | Cas limite : sel vide |
| `testGenerateIncludesSaltInHash` | Intégration du sel |
| `testGenerateSaltProducesCorrectLength` | Longueur du sel |
| `testGenerateSaltProducesRandomStrings` | Caractère aléatoire |
| `testGenerateSaltWithLengthZero` | Sel de longueur zéro |
| `testGenerateSaltWithLargeLength` | Sel de grande taille |
| `testGenerateUniqueProducesUniqueHashes` | Unicité garantie |
| `testGenerateUniqueProducesValidHashFormat` | Format valide |
| `testHashConsistencyAcrossMultipleCalls` | Cohérence entre appels |
| `testGenerateWithSpecialCharacters` | Caractères spéciaux |
| `testGenerateWithUnicodeCharacters` | Support Unicode |

### Détection de conflits — 1 test (`tests/ReservationConflictTest.php`)

Test placeholder actif. Les scénarios d'intégration ci-dessous sont commentés dans le fichier et peuvent être activés avec une base de données de test :

- `testNoConflictForDistinctTimes` — créneaux distincts
- `testConflictOnOverlap` — chevauchement détecté
- `testNoConflictAtExactBoundary` — réservations adjacentes
- `testConflictWhenNewTimeIsWithinExisting` — créneau inclus dans un existant
- `testConflictWhenNewTimeEncompassesExisting` — créneau englobant un existant
- `testConflictDetectionWithRoomId` — lookup par ID de salle
- `testConflictDetectionWithRoomName` — lookup par nom de salle
- `testConflictDetectionExcludingReservation` — exclusion d'une réservation (pour modification)

---

## Écrire un nouveau test

### Template de test unitaire

```php
<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class MyClassTest extends TestCase
{
    public function testSomething(): void
    {
        $this->assertEquals('expected', MyClass::method('input'));
    }
}
```

### Assertions courantes

```php
$this->assertTrue($result);
$this->assertFalse($result);
$this->assertEquals($expected, $actual);
$this->assertNotEquals($notExpected, $actual);
$this->assertNull($result);
$this->assertNotNull($result);
$this->assertIsArray($result);
$this->assertIsInt($result);
$this->assertIsString($result);
$this->assertCount(3, $array);
$this->assertEmpty($array);
$this->assertStringContainsString('needle', 'haystack');
$this->assertMatchesRegularExpression('/pattern/', $string);
```

### Conventions de nommage

- Classe : `MyClassTest.php` (suffixe `Test`)
- Méthode : `testNomDescriptif()` (préfixe `test`)
- Nom explicite : `testGetAllReturnsArray()` plutôt que `testMethod1()`

---

## Tests d'intégration (avec base de données)

### Configuration

**1. Créer la base de test**

```bash
mysql -u root -p -e "CREATE DATABASE meetrooms_test;"
mysql -u root -p meetrooms_test < sql/init_db.sql
```

**2. Mettre à jour `phpunit.xml`**

```xml
<env name="DB_HOST" value="localhost" />
<env name="DB_NAME" value="meetrooms_test" />
<env name="DB_USER" value="root" />
<env name="DB_PASSWORD" value="your_password" />
```

Ou via variables d'environnement :

```bash
DB_HOST=localhost DB_NAME=meetrooms_test DB_USER=root DB_PASSWORD= ./vendor/bin/phpunit tests/Integration/
```

### Exemple : test d'intégration sur le modèle `Reservations`

```php
<?php
namespace Tests\Integration;

use App\Models\Reservations;
use PHPUnit\Framework\TestCase;
use PDO;

class ReservationsModelTest extends TestCase
{
    private static PDO $pdo;

    public static function setUpBeforeClass(): void
    {
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8',
            getenv('DB_HOST') ?: 'localhost',
            getenv('DB_NAME') ?: 'meetrooms_test'
        );
        self::$pdo = new PDO($dsn, getenv('DB_USER') ?: 'root', getenv('DB_PASSWORD') ?: '');
        self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    protected function setUp(): void
    {
        self::$pdo->exec('DELETE FROM reservations');
        self::$pdo->exec('DELETE FROM users');
        self::$pdo->exec('DELETE FROM rooms');
        self::$pdo->exec('DELETE FROM buildings');
        self::$pdo->exec("INSERT INTO buildings (code, name) VALUES ('A', 'Building A')");
        self::$pdo->exec("INSERT INTO rooms (building_id, code, name, capacity, features)
                          VALUES (1, 'A101', 'Salle A', 20, 'projector')");
        self::$pdo->exec("INSERT INTO users (username, email, password, salt)
                          VALUES ('test', 'test@example.com', 'hash', 'salt')");
    }

    public function testSaveCreatesReservation(): void
    {
        $id = Reservations::save([
            'room_id' => 1,
            'start_datetime' => '2025-10-30 10:00:00',
            'end_datetime'   => '2025-10-30 11:00:00',
            'user_id'        => 1,
            'comment'        => 'Test',
        ]);
        $this->assertGreaterThan(0, $id);
    }

    public function testIsConflictingDetectsOverlap(): void
    {
        Reservations::save([
            'room_id' => 1,
            'start_datetime' => '2025-10-30 10:00:00',
            'end_datetime'   => '2025-10-30 11:00:00',
            'user_id' => 1, 'comment' => '',
        ]);
        $this->assertTrue(Reservations::isConflicting(1, '2025-10-30 10:30:00', '2025-10-30 11:30:00'));
    }

    public function testIsConflictingAllowsAdjacentSlots(): void
    {
        Reservations::save([
            'room_id' => 1,
            'start_datetime' => '2025-10-30 10:00:00',
            'end_datetime'   => '2025-10-30 11:00:00',
            'user_id' => 1, 'comment' => '',
        ]);
        $this->assertFalse(Reservations::isConflicting(1, '2025-10-30 11:00:00', '2025-10-30 12:00:00'));
    }
}
```

---

## CI

Les tests sont exécutés automatiquement via `.github/workflows/ci.yml` sur chaque push et PR vers `dev`, `recette` et `prod`.

Comportement selon la branche :

| Branche | Commande |
|---|---|
| `dev` | `./vendor/bin/phpunit tests/` |
| `recette` / `prod` | `./vendor/bin/phpunit --fail-on-warning --fail-on-risky tests/` |

---

## Dépannage

**Erreur de connexion à la base**
- Vérifier les variables d'environnement ou `phpunit.xml`
- S'assurer que le serveur de base de données est démarré
- Vérifier que la base `meetrooms_test` existe

**Erreurs de mocking PDO**
- `Core\Model` utilise un cache statique de connexion — préférer des tests d'intégration avec une vraie base plutôt que des mocks PDO

**Isolation des tests**
- Chaque test doit être indépendant
- Utiliser `setUp()` pour réinitialiser les données entre tests
