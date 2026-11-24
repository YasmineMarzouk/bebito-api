# Cart & Checkout Flow

## Storage and boundaries

The cart is client-side only. `bebito-blossom-boutique/src/context/CartContext.tsx` persists display-oriented cart lines in browser `localStorage`; there are no `carts` or `cart_items` migrations, models, or API endpoints. Browser prices, stock, names, images, and totals are therefore treated as untrusted display data.

The checkout API accepts only `variant_id` and `quantity` as purchasable line inputs. Guest checkout is an existing supported route; when a valid customer session is present, `CreateCheckoutController` derives the order user from the `customer` guard rather than client input. Saved customer addresses are not part of the current checkout contract.

## Successful checkout

```text
Browser localStorage cart
  -> POST /api/v1/store/checkout + Idempotency-Key UUID
  -> CreateCheckoutRequest: bounds, distinct variants, key validation
  -> CreateCheckoutUseCase database transaction (up to three deadlock attempts)
     -> lock/revalidate active variant and canonical inventory item
     -> verify active TN and TND (three decimal places)
     -> derive product.price_amount subtotal and server shipping
     -> lock/revalidate coupon and calculate discount when supplied
     -> create order, item and shipping-address snapshots, pending COD payment,
        and pending status history
     -> atomically increment coupon usage and decrement inventory
     -> append order_placed inventory movements
  -> commit
  -> CheckoutOrderOutput
  -> clear browser cart only after a successful response
```

## Retry contract

`Idempotency-Key` is required as a UUID header. The order stores the key and a SHA-256 fingerprint of the authenticated identity and normalized checkout request. A repeat with the same key and identical intent returns the original order without a second payment, coupon redemption, inventory decrement, or movement. Reusing a key with changed checkout details returns `422` with error code `4201`.

The unique order key is the cross-connection backstop for concurrent duplicate submissions. If a competing transaction wins the unique insert, the losing transaction rolls back and returns the already-created order only when the fingerprint matches.

## Failure behavior

All order writes, coupon count updates, stock decrements, and inventory movements share the checkout transaction. Availability, country, currency, coupon, and validation failures leave no order or inventory effect. There are no checkout notifications or jobs in the current implementation.
