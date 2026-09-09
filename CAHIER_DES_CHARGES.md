# Cahier des charges
## Application de gestion ERP — Univers Pâtissier

---

## 1. Présentation du projet

### 1.1 Contexte
Univers Pâtissier a besoin d'un système de gestion intégré (ERP) permettant de piloter l'ensemble de son activité : gestion des stocks, des achats, des ventes, du point de vente (caisse), des finances et des rapports d'activité, au sein d'une application web unique et sécurisée.

### 1.2 Objectifs
- Centraliser la gestion des produits, des stocks et des packs/coffrets dans un outil unique.
- Fluidifier le cycle achat → stock → vente → encaissement.
- Offrir un point de vente (POS) rapide pour la vente en boutique.
- Donner une visibilité financière claire (trésorerie, profits, dépenses).
- Sécuriser les accès par rôles et permissions selon les responsabilités de chaque collaborateur.

### 1.3 Règle métier fondamentale
Le stock physique est toujours géré **en unités de base** (ex. : croissant à l'unité). Les **packs/coffrets** (ex. : "Boîte de 6 croissants", "Carton de 24") sont une **vue de présentation commerciale** : leur vente déduit automatiquement le nombre d'unités correspondant dans le stock réel, sans jamais créer un stock parallèle propre au pack.

---

## 2. Périmètre fonctionnel

### 2.1 Authentification et sécurité
- Connexion sécurisée (écran de login).
- Récupération de mot de passe oublié par e-mail.
- Réinitialisation de mot de passe.
- Gestion des rôles et permissions (super administrateur, caissier, magasinier, etc.) avec accès restreint par module : Commerce, Stock, Finance, Administration.

### 2.2 Tableau de bord
- Indicateurs clés en un coup d'œil : chiffre d'affaires, achats, dépenses, profit net.
- Graphiques d'évolution de l'activité.
- Alertes de stock bas / produits en rupture.
- Top des produits les plus vendus.

### 2.3 Gestion des produits
- Catégories de produits.
- Unités de mesure.
- Fiches produits complètes (prix, description, photo, seuils de stock).
- **Packs / coffrets** : composition à partir de plusieurs produits, prix de vente dédié, déduction/réintégration automatique du stock des composants.

### 2.4 Achats
- Création et suivi des commandes fournisseurs.
- Confirmation de réception et mise à jour automatique du stock.
- Suivi des paiements aux fournisseurs.
- Retours d'achat (marchandise renvoyée au fournisseur).

### 2.5 Ventes
- Création de ventes avec sélection de produits/packs, calcul automatique des totaux.
- Devis, avec conversion en vente validée.
- Retours de vente (remboursement / avoir client).
- Historique complet des ventes par client.

### 2.6 Point de vente (POS / Caisse)
- Interface de caisse rapide pensée pour la vente en boutique.
- Gestion de sessions de caisse (ouverture / fermeture, contrôle du fond de caisse).
- Gestion de plusieurs caisses physiques (si plusieurs points de vente ou postes).
- Historique des tickets de caisse.

### 2.7 Gestion des stocks
- Suivi de l'état de stock en temps réel par produit et par entrepôt.
- Transferts de stock entre entrepôts/points de vente.
- Ajustements de stock (casse, inventaire, correction).
- Alertes de péremption et de stock bas.

### 2.8 Finances
- Gestion des dépenses, classées par catégorie.
- Comptes de paiement (caisse, banque, mobile money, etc.) avec suivi du solde.
- Bilan comptable simplifié, balance de vérification, flux de trésorerie.

### 2.9 Rapports
Plus d'une quinzaine de rapports prêts à l'emploi, dont :
- Profit & perte, rapport fiscal.
- Achats & ventes, achats/ventes par produit.
- Rapport de stock, péremption, ajustements.
- Produits tendances, rapport par article.
- Paiements fournisseurs/clients.
- Rapport de caisse (POS).
- Journal d'activité (traçabilité des actions utilisateurs).

### 2.10 Contacts
- Fiches clients (historique d'achats, coordonnées).
- Fiches fournisseurs (historique d'achats, coordonnées).
- Groupes de clients (segmentation, le cas échéant).

### 2.11 Administration et paramétrage
- Gestion des utilisateurs et de leurs rôles.
- Paramètres de l'entreprise (coordonnées, logo).
- Gestion des entrepôts/points de vente.
- Paramétrage des factures (numérotation, mentions légales).
- Taux de TVA configurables.

### 2.12 Export et impression
- Génération de documents PDF (factures, bons, rapports).
- Export Excel des données et rapports.

---

## 3. Exigences non fonctionnelles

| Catégorie | Exigence |
|---|---|
| Technologie | Application web, développée avec le framework Laravel (PHP) |
| Compatibilité | Accessible depuis un navigateur web standard (poste fixe, tablette) |
| Sécurité | Authentification obligatoire, permissions par rôle, traçabilité des actions (journal d'activité) |
| Performance | Temps de réponse adaptés à un usage en boutique (caisse rapide) |
| Données | Sauvegardes régulières de la base de données |
| Hébergement | À définir avec le client (serveur dédié / mutualisé / cloud) |

---

## 4. Hors périmètre (à discuter si besoin)
- Application mobile native (le POS reste accessible via navigateur web).
- Intégration avec un système de paiement en ligne tiers.
- Gestion multi-devises.
- Module de fabrication / recettes (transformation matières premières → produits finis), si non explicitement demandé.

*(Cette section est à ajuster avec le client : elle liste ce qui n'est pas prévu par défaut, pour éviter toute ambiguïté contractuelle.)*

---

## 5. Livrables
- Application ERP fonctionnelle, déployée sur l'environnement défini avec le client.
- Comptes de démonstration et données d'exemple pour la prise en main.
- Documentation utilisateur de base (prise en main des modules principaux).
- Code source de l'application.

---

## 6. Étapes suivantes proposées
1. Validation du présent cahier des charges par le client (ajustements, priorités).
2. Recueil des données réelles du client (produits, fournisseurs, clients, taux de TVA en vigueur).
3. Paramétrage de l'environnement de production.
4. Formation des utilisateurs (administrateur, caissiers, magasiniers).
5. Mise en production et accompagnement post-lancement.

---

*Document à adapter selon les retours du client avant validation finale.*
