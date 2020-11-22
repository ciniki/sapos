# The shipping setup list:

- Curbside Pickup flag (not sure if flag right idea)
- Instore Pickup Option, Curbside Pickup Option.
- Display curbside pickup in shipping cost on invoices
- add field shipping_profile_id to ciniki_sapos_invoice_items

- Orders UI should be stripped of Dream centric stuff
- Setup orders UI for tracking what needs to be shipped or pickup

- Add setting name for Instore Pickup flag (Instore pickup, curbside pickup, etc)

Shipping Profiles
    - stores in sapos
    - available via hooks
    - items are supplied with shipping_profile_id
    - profiles are used to calculate shipping costs
    - some profiles are curbside pickup only, others contain both
    - shipping profile can contain quantity
        - The number of that shipping profile on an invoice that would
          qualify for single shipping rate

        - maybe profile consists of min/max quantity for 


ciniki_sapos_shipping_profiles
- id
- min_quantity
- max_quantity
- min amount
- max amount
- ship cost US
- ship cost Canada
- Ship cost International



