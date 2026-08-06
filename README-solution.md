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

Je compte utiliser les "lockForUpdate" de Laravel afin de gérer les problèmes de concurrence.

## Solution retenue
J'ai suivi mon plan initial: une table pour les réservations et une interface pour réserver via une ligne de produit. 

Toutefois, en cours de route, je me suis dit qu'ajoute une "quantity_reserved" sur les "order_lines" me permettrait de charger plus rapidement ces données dans les lignes de la commande et de mieux gérer la concurrence. En effet, si on bloque une quantité sur "order_lines", ça permet de s'assurer qu'aucune autre quantité n'est modifiée en même temps.

Pour ce qui est de la concurrence, j'ai bloqué les `rows` concernées via "lockForUpdate", le tout dans une transaction, ce qui me permet de créer InventoryReservation et de lock la ligne "OrderLine" ainsi que la ligne "InventoryBalance" liée au produit. De cette manière, on peut modifier la quantité réservée dans "OrderLine" ainsi que d'éviter que la quantité diminue sur "InventoryBalance" pendant qu'on finalise la réservation. Même si celle-ci n'est pas modifiée par une réservation, on ne veut pas réserver en même temps que le stock réel bouge.

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
