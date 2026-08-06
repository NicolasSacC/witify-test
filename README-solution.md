# Solution proposée pour les réservations

## Hypothèse initiale
Selon moi, voici comment je vais procéder pour compléter la fonctionnalité:

- Table "Réservations" (inventory_reservations)
  - id
  - product_id
  - user_id 
  - warehouse_id
  - quantity_reserved
- Interface pour réserver via une ligne de produit
  - Popup qui s'ouvre avec les champs suivants
    - Quantité demandée (champ disabled/info)
    - Quantité déjà réservée (champ disabled/info)
    - Quantité restant à réserver (champ disabled/info)
    - Quantité à réserver (entier positif)
    - Entrepôt dans lequel réserver (nom + quantité disponible dans un `select`)

Étant donné que ce n'est pas spécifié explicitement, je peux inférer qu'on voudra réserver une quantité dans une entrepôt particulier par simplicité. Si on veut réserver dans plusieurs entrepôts, on peut le faire en plusieurs étapes.  

## Solution retenue


## Améliorations possibles 
Étant donné que le temps était limité, je n'ai pas pu compléter l'ensemble des fonctionnalités pour que je considère ce système comme complet. Si c'était possible, j'aurais ajouté:

- Un système pour réserver l'ensemble des items de manière intelligente
  - Pour chaque item, on trouve un entrepôt ayant la quantité demandée et on créé la réservation
  - Erreur si aucun n'entrepôt offre le stock disponible 
    - OU split la réservation entre plusieurs entrepôts
  - Validation manuelle à la fin
- Possibilité d'ajouter une note sur une réservation 
- Possibiltié de gérer et de "fulfill" une réservation (ex: quand la commande est passée, on transforme la réservation en un mouvement de stock)
- Ajouter les réservations existantes lors de la prise de réservation sur la commande
