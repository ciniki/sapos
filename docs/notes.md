Hooks into other modules:
- sapos/itemPaid($ciniki, $business_id, $object, $object_id)
	- This can be called in other modules to mark items as paid
	- In events this will mark the participant as paid
- sapos/itemSold($ciniki, $business_id, $object, $object_id)
	- This can be called in other modules to mark items as sale pending, reduce inventory
	- in artcatalog this will mark the piece with sold flag
- sapos/itemShipped($ciniki, $business_id, $object, $object_id)
	- This can be called in other modules to mark item as shipped, reduce inventory
- sapos/itemCancelled($ciniki, $business_id, $object, $object_id)
	- This is call when an invoice is cancelled, and inventory should be adjusted

- invoices can be joined (invoice_items moved from one invoice to another)
	- this allows customers to pay one invoice instead of multiple transactions

- all transactions are assumed to be in the currency of the business

Invoice Status
==============
- 10 - Shopping cart
	- this status is used when the customer is building a shopping cart online
	- this status should not be used for account purposes, it's not officially an invoice yet

- 20 - Creating
	- The invoice is being created by a user of the business.  It's not yet officially
	  an invoice in the system, and should not be considered for accounting purposes.
	- typically invoices will not stay in this state a long time, just while the user adds items.

- 30 - Entered
	- the invoice is entered in the system, no more changes should be made to the invoice once entered.
	- This invoice is now used in accounting reports

- 40 - partial payment/deposit
	- there has been some payment made for the invoice
	- this might be a deposit to hold a spot or reserve and item
	- this could be done when they pay partially over several days

- 50 - paid
	- the invoice is paid fully, no more changes should happen to the items on the invoice

- 55 - refunded
	- The invoice was refunded for some reason
	- any transactions will still be used in accounting reports
	- most of the time a cancellation should generate a refund not void the invoice

- 60 - void
	- The invoice has been cancelled, or voided.
	- the invoice will NOT be included in accounting reports
	- typically something went wrong, either the customer cancelled the order, or there was a mistake
	  and another invoice is generated to replace this one
	- there shouldn't be any real payment transactions for this invoice, as they will not be accounted for
	- any testing invoices should be put into void mode when no actual money was transferred


Taxes
=====
- Products/services will not be directly tied to taxes, they are give a tax type 
  which is later mapped to taxes.

- The taxes available are stored in the ciniki_sapos_taxes table.
	- name, percentage, taxtypes, flags
	- the taxtypes corresponds to taxtypes setup for the business

- Tax Types are used to map which types of taxes are applicable to which products
	- example would be food/non-food products
	- Germany: food (7%), non-food (19%)
	- if taxtypes for a invoice item is 0, then no taxes are applied.

- Tax Types are stored in ciniki_sapos_settings as taxes-type-1-name

- The tax types should be easy to understand names like Food/Groceries/Books/Clothing/Services/etc.
	- tax type names should not be the taxes themselves.  This is an abstraction layer.

- Each item on an invoice has the bit field(32bit) for taxtypes

- The reason to separate taxes and taxtypes, is to allow businesses to setup their
  business products once with the tax types, and then alter taxes in the future.

- If a region introduces a new tax, it can be added to the taxes table, without having
  to update the tax types and update all products.

- There is a hard limit of 32 tax types, but they can be mixed and matched to apply multiple types
  to a single product.

- In the future, more rules can be applied to taxes using the sapos_taxes table 
  and shipping destination if required.


An example for in Ontario, Canada:
	Taxtypes:
		taxtypes-1-name: Taxable
	Taxes:
		Tax: HST (13%), taxtypes: 0x01

	Product taxtypes:
		groceries: 0x00 (no taxes)
		t-shirts: 0x01 (apply HST)

An example for Germany	
	Taxtypes:
		taxtypes-1-name: Food
		taxtypes-2-name: Non-Food

	Taxes:
		Food (7%), taxtypes: 0x01
		Non-Food (19%), taxtypes: 0x02

	Product taxtypes:
		groceries: 0x01 (apply 7%)
		Book: 0x02 (apply 19%)

An example of Quebec, Canada:
	Taxtypes:
		taxtypes-1-name: General
	
	Taxes:
		GST (5%), taxtypes: 0x01
		QST (9.975%), taxtypes: 0x01

	Product taxtypes:
		groceries: 0x00 (no taxes)
		t-shirts: 0x01 (apply GST and QST)

Shopping Carts
==============
- There will be a need for shopping cart feature on businesses website in the future.
- Invoice will be used a shopping cart, with the status of 10 while the user is still shopping.
- When the user checks out and pays, the invoice status will go to 50.


Future Shipping Tables
======================
- There will be a need to keep track of shipments and the items from each invoice that shipped.

create table ciniki_sapos_shippers (
	id int not null auto_increment,
	uuid char(36) not null,
	business_id int not null,
	name varchar(200) not null,
	shipment_tracking_url varchar(250) not null,
	date_added datetime not null,
	last_updated datetime not null,
	primary key (id),
	index sync (uuid, business_id, last_updated)
) ENGINE='InnoDB', COMMENT='v1.01';
	
#
# shipments could refer to multiple invoices, let each individual item link to an invoice
# from which the shipment originated
#
create table ciniki_sapos_shipments (
	id int not null auto_increment,
	uuid char(36) not null,
	business_id int not null,
	status tinyint not null,
	shipper int not null,
	tracking_code varchar(200) not null,
	date_shipped datetime not null,
	estimated_arrival_date date not null,
	date_added datetime not null,
	last_updated datetime not null,
	primary key (id),
	index sync (uuid, business_id, last_updated)
) ENGINE='InnoDB', COMMENT='v1.01';

create table ciniki_sapos_shipment_items (
	id int not null auto_increment,
	uuid char(36) not null,
	business_id int not null,
	shipment_id int not null,
	invoice_id int not null,
	item_id int not null,
	date_added datetime not null,
	last_updated datetime not null,
	primary key (id),
	index (shipment_id, invoice_id, item_id),
	index sync (uuid, business_id, last_updated)
) ENGINE='InnoDB', COMMENT='v1.01';
