
- when an invoice is paid, have callback it each invoice item's module to pos/itemPaid(obj, obj_id).
	- this will allow events to mark the status of a participant as paid

- invoices can be joined (invoice_items moved from one invoice to another)
	- this allows customers to pay one invoice instead of multiple transactions

- all transactions are assumed to be in the currency of the business

