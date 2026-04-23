# Recommandations de Protection des Branches (GitFlow)

Ce document détaille la stratégie de protection recommandée pour le projet **MeetRooms** sur GitHub.
Il vise à garantir que le code déployé en production `prod` est toujours certifié par l'environnement de recette et testé avec succès, tout en gardant une souplesse pour les développeurs.

---

## 1. Branche `prod` (Production)
C'est la branche la plus critique. Tout ce qui y atterrit est expédié automatiquement sur le serveur via le pipeline `.github/workflows/deploy.yml`.

**Réglages GitHub (Branch Protection Rules) :**
- [x] **Require a pull request before merging**
  - *Require approvals* : 1 minimum (idéalement le Tech Lead ou l'Architecte).
- [x] **Require status checks to pass before merging**
  - *Require branches to be up to date before merging* : Activé.
  - S'assurer que le workflow `Run Tests & Build` est coché et **doit** être vert.
- [x] **Do not allow bypassing the above settings**
  - Même les administrateurs du dépôt ne peuvent pas contourner cette règle. Cela protège contre les *force pushes* par erreur.
- [x] **Restrict who can push to matching branches**
  - Spécifiez uniquement les Mainteneurs ou une équipe "DevOps". Pas de Push direct.

## 2. Branche `recette` (Staging / Pre-prod)
Cette branche rassemble les fonctionnalités validées avant une release. Elle correspond au serveur de test client. 

**Réglages GitHub :**
- [x] **Require a pull request before merging**
  - Pas d'approbation stricte obligatoire (pragmatisme pour les petites équipes), mais la PR force la formalisation de la release.
- [x] **Require status checks to pass before merging**
  - Tests unitaires stricts (`--fail-on-warning` est actif sur cette branche dans le CI).
- Le Push direct doit être interdit ou formellement déconseillé. On fusionne toujours depuis `dev`.

## 3. Branche `dev` (Développement Actif)
C'est la branche d'intégration où l'équipe fusionne son travail au quotidien en provenance des `feature/*`.

**Réglages GitHub :**
- [x] **Require status checks to pass before merging**
  - Le pipeline CI de test standard s'y exécute. Il gère seulement la "casse franche" (pas de fail-on-warning).
- Les Push directs sont traditionnellement déconseillés, mais pour les "hotfixes" mineurs de développement, on peut autoriser le Tech Lead à push dessus pour gagner du temps.

---

### Résumé du Flux Physique

1. Le Développeur A travaille sur `feature/login`.
2. Il crée une Pull Request vers `dev`. Le CI (Tests) se lance. Il est vert, il merge.
3. Quand un cap est franchi, on crée une Pull Request `dev` ➔ `recette`. Le CI (Tests Stricts) tourne. Le client valide sur l'environnement de recette.
4. Une fois validé, on fait une Pull Request `recette` ➔ `prod`. Dès qu'elle est mergée (nécessitant une review formelle), le pipeline `deploy.yml` compile l'image Docker, la sauvegarde dans le registre, et met le code en ligne automatiquement.
