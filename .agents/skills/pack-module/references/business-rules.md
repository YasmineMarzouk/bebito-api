# Pack Business Rules

## Definition

A Pack is a virtual catalog item made from at least two existing standalone products. Components remain independently sellable. Packs have their own catalog identity, merchandising attributes, price, SKU, images, and variants, but no independent physical inventory.

## Pack fields

Use existing catalog conventions for:
- title, slug, description
- selling price
- compare-at price
- calculated discount amount/percentage
- unique SKU
- low-stock threshold
- categories, collection, gender, tags, images
- variants and publication/status lifecycle

Selling price is manually managed. Compare-at price is derived from component product prices multiplied by component quantities. Discount is derived using existing monetary conventions. Component price changes must not alter Pack selling price.

## Composition

- At least two distinct standalone products.
- Component quantity is a positive integer.
- A product may appear only once as a composition row; update its quantity instead.
- A Pack may never contain a Pack or any other non-product catalog entity.
- Components must satisfy existing eligibility rules.
- Composition and variant mappings are editable while the Pack has never been published.
- First publication permanently locks composition and component variant mappings.
- Deactivation, unpublishing, archiving, or reactivation never unlocks composition.
- The backend must enforce immutability at every applicable entry point; UI read-only behavior is not a security control.
- Other permitted catalog attributes remain editable after publication.
- Publication must reject invalid composition or variant mappings.

## Variants

Every Pack variant explicitly maps to one component product variant for every component. Matching size labels may be a convenient default, but persisted IDs—not labels—are authoritative. Missing, inactive, invalid, or incompatible component variants make the Pack variant unavailable or prevent publication according to validation rules. Never silently substitute a variant.

## Pricing

- Pack selling price is manual.
- Compare-at price = sum(component current price × component quantity).
- Discount is derived from selling and compare-at prices.
- Use existing currency precision, rounding, and validation.
- A component price change updates/recalculates the affected Pack compare-at price/projection without changing Pack selling price.
- Draft composition changes recalculate compare-at price.
- Historical order prices remain unchanged.

## Inventory

Pack stock is derived and never stored as a Pack inventory balance. For each Pack variant:

`available_pack_units = min(floor(component_available_units / required_component_quantity))`

Total Pack stock is the sum of available units across Pack variants. Low-stock state uses calculated total stock and the Pack's threshold; threshold never changes purchase eligibility.

## Lifecycle and orders

- Published composition is immutable forever.
- Orders snapshot Pack identity, price, variant, composition, component product/variant identifiers, quantities, and relevant labels/SKUs.
- Historical order processing, cancellation, and restoration must use the original order snapshot, not current Pack composition.
- Pack order edits use the existing order editing mechanism and shared inventory system.
- Product and Pack checkout share inventory globally.
