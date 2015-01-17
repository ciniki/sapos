//
// This panel will create or edit an invoice
//
function ciniki_sapos_invoice() {
	this.invoiceTypes = {};
	this.invoiceStatuses = {
		'10':'Entered',
		'20':'Pending Manufacturing',
		'30':'Pending Shipping',
		'40':'Payment Required',
		'50':'Paid',
		'55':'Refunded',
		'60':'Void',
		};
	this.orderStatuses = {
		'10':'Incomplete',
		'15':'On Hold',
		'20':'Pending Manufacturing',
		'30':'Pending Shipping',
		'40':'Payment Required',
		'50':'Fulfilled',
		'55':'Refunded',
		'60':'Void',
		};
	this.paymentStatuses = {
		'0':'None Required',
		'10':'Required',
		'40':'Deposit',
		'50':'Paid',
		'55':'Refunded',
		};
	this.shippingStatuses = {
		'0':'None',
		'10':'Required',
		'30':'Partial Shipment',
		'50':'Shipped',
		};
	this.manufacturingStatuses = {
		'0':'None',
		'10':'Required',
		'30':'In Progress',
		'50':'Completed',
		};
	this.taxTypeOptions = {};
	this.transactionTypes = {
		'10':'Deposit',
		'20':'Payment',
		'60':'Refund',
		};
	this.transactionSources = {
		'10':'Paypal',
		'20':'Square',
		'50':'Visa',
		'55':'Mastercard',
		'60':'Discover',
		'65':'Amex',
		'90':'Interac',
		'100':'Cash',
		'105':'Check',
		'110':'Email Transfer',
		'120':'Other',
		};
	this.invoiceFlags = {
		'1':{'name':'Hide Savings'},
		};
	this.colours = {};
	this.init = function() {
		//
		// The invoice panel
		//
		this.invoice = new M.panel('Invoice',
			'ciniki_sapos_invoice', 'invoice',
			'mc', 'medium mediumaside', 'sectioned', 'ciniki.sapos.invoice.invoice');
		this.invoice.invoice_id = 0;
		this.invoice.pricepoint_id = 0;
		this.invoice.nextShipmentNumber = '1';
		this.invoice.data = {};
		this.invoice.prevnext = {'prev_id':0, 'next_id':0, 'list':[]};
		this.invoice.sections = {
			'details':{'label':'', 'aside':'yes', 'list':{
				'invoice_number':{'label':'Invoice #'},
//				'invoice_type_text':{'label':'Type'},
				'po_number':{'label':'PO #'},
				'salesrep_id_text':{'label':'Sales Rep'},
//				'status_text':{'label':'Status'},
				'payment_status_text':{'label':'Payment'},
				'shipping_status_text':{'label':'Shipping'},
				'manufacturing_status_text':{'label':'Manufacturing'},
				'invoice_date':{'label':'Invoice Date'},
				'due_date':{'label':'Due Date'},
				'flags_text':{'label':'Options', 'visible':'no'},
				'pricepoint_id_text':{'label':'Pricepoint', 'visible':'no'},
				'submitted_by':{'label':'Submitted By', 'visible':'no'},
				}},
			'customer_notes':{'label':'Customer Notes', 'aside':'yes', 'visible':'no', 'type':'html'},
			'internal_notes':{'label':'Internal Notes', 'aside':'yes', 'visible':'no', 'type':'html'},
			'customer_details':{'label':'', 'aside':'yes', 'type':'simplegrid', 'num_cols':2,
				'cellClasses':['label',''],
				'addTxt':'Edit',
				'addFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_sapos_invoice.showInvoice();\',\'mc\',{\'next\':\'M.ciniki_sapos_invoice.updateInvoiceCustomer\',\'customer_id\':M.ciniki_sapos_invoice.invoice.data.customer_id});',
				'changeTxt':'Change customer',
				'changeFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_sapos_invoice.showInvoice();\',\'mc\',{\'next\':\'M.ciniki_sapos_invoice.updateInvoiceCustomer\',\'customer_id\':0});',
				},
			'shipping':{'label':'', 'aside':'yes', 'visible':'no', 'list':{
//				'shipping_name':{'label':'Ship To'},
				'shipping_address':{'label':'Ship To'},
				}},
			'billing':{'label':'', 'aside':'yes', 'visible':'no', 'list':{
//				'billing_name':{'label':'Bill To'},
				'billing_address':{'label':'Bill To'},
				}},
			'work':{'label':'', 'aside':'yes', 'visible':'no', 'list':{
//				'billing_name':{'label':'Bill To'},
				'work_address':{'label':'Work Location'},
				}},
			'shipitems':{'label':'', 'type':'simplegrid', 'num_cols':7,
				'headerValues':['Description', 'Qty', 'Inv', 'B/O', 'Shp', 'Price', 'Total'],
				'headerClasses':['', 'alignright', 'alignright', 'alignright', 'alignright', 'alignright', 'alignright'],
				'cellClasses':['multiline', 'alignright', 'alignright', 'alignright', 'alignright', 'multiline alignright', 'multiline alignright'],
				'addTxt':'Add',
				'addFn':'M.ciniki_sapos_invoice.editItem(\'M.ciniki_sapos_invoice.showInvoice();\',0,M.ciniki_sapos_invoice.invoice.invoice_id);',
				'noData':'No shipments',
				},
			'items':{'label':'', 'type':'simplegrid', 'num_cols':3,
				'headerValues':['Description', 'Quantity/Price', 'Total'],
				'headerClasses':['', 'alignright', 'alignright'],
				'cellClasses':['multiline', 'multiline alignright', 'multiline alignright'],
				'addTxt':'Add',
				'addFn':'M.ciniki_sapos_invoice.editItem(\'M.ciniki_sapos_invoice.showInvoice();\',0,M.ciniki_sapos_invoice.invoice.invoice_id);',
				},
			'tallies':{'label':'', 'type':'simplegrid', 'num_cols':2,
				'headerValues':null,
				'cellClasses':['alignright','alignright'],
				},
			'transactions':{'label':'', 'type':'simplegrid', 'num_cols':3,
				'headerValues':null,
				'cellClasses':['', '', 'alignright'],
				},
			'shipments':{'label':'Shipments', 'type':'simplegrid', 'num_cols':4,
				'headerValues':['#', 'Pack Date', 'Ship Date', 'Status'],
				'cellClasses':[''],
				'addTxt':'Add Shipment',
				'addFn':'M.startApp(\'ciniki.sapos.shipment\',null,\'M.ciniki_sapos_invoice.showInvoice();\',\'mc\',{\'shipment_id\':0, \'invoice_id\':M.ciniki_sapos_invoice.invoice.invoice_id,\'shipment_number\':M.ciniki_sapos_invoice.invoice.nextShipmentNumber});',
				},
			'_buttons':{'label':'', 'buttons':{
				'record':{'label':'Record Transaction', 'fn':'M.ciniki_sapos_invoice.editTransaction(\'M.ciniki_sapos_invoice.showInvoice();\',0,M.ciniki_sapos_invoice.invoice.invoice_id,\'now\',M.ciniki_sapos_invoice.invoice.data.balance_amount_display);'},
				'terminal':{'label':'Process Payment', 'fn':'M.startApp(\'ciniki.sapos.terminal\',null,\'M.ciniki_sapos_invoice.showInvoice();\',\'mc\',{\'detailsFn\':M.ciniki_sapos_invoice.terminalDetails});'},
				'submitorder':{'label':'Submit Order', 'fn':'M.ciniki_sapos_invoice.submitOrder(M.ciniki_sapos_invoice.invoice.invoice_id);'},
				'picklist':{'label':'Print Pick/Pack', 'fn':'M.ciniki_sapos_invoice.printPickList(M.ciniki_sapos_invoice.invoice.invoice_id);'},
				'print':{'label':'Print Invoice', 'fn':'M.ciniki_sapos_invoice.printInvoice(M.ciniki_sapos_invoice.invoice.invoice_id);'},
				'printquote':{'label':'Print Quote', 'fn':'M.ciniki_sapos_invoice.printQuote(M.ciniki_sapos_invoice.invoice.invoice_id);'},
				'printenv':{'label':'Print Envelope', 'fn':'M.ciniki_sapos_invoice.printEnvelope(M.ciniki_sapos_invoice.invoice.invoice_id);'},
				'email':{'label':'Email Customer', 'fn':'M.ciniki_sapos_invoice.emailCustomer(\'M.ciniki_sapos_invoice.showInvoice();\',M.ciniki_sapos_invoice.invoice.data);'},
				'delete':{'label':'Delete Invoice', 'fn':'M.ciniki_sapos_invoice.deleteInvoice(M.ciniki_sapos_invoice.invoice.invoice_id);'},
				}},
//			'invoice_notes':{'label':'Notes', 'aside':'left', 'type':'htmlcontent'},
//			'internal_notes':{'label':'Notes', 'aside':'left', 'type':'htmlcontent'},
			};
		this.invoice.sectionData = function(s) {
			if( s == 'shipitems' ) { return this.data['items']; }
			if( s == 'invoice_notes' || s == 'internal_notes' || s == 'customer_notes' ) { return this.data[s].replace(/\n/g, '<br/>'); }
			if( s == 'details' || s == 'billing' || s == 'shipping' || s == 'work' ) { return this.sections[s].list; }
			return this.data[s];
		};
		this.invoice.listLabel = function(s, i, d) {
			return d.label;
		};
		this.invoice.listValue = function(s, i, d) {
			if( i == 'invoice_number' ) {
				if( this.data.invoice_type == 11 ) {
					return 'Monthly <span class="subdue">[' + this.data['status_text'] + ']</span>';
				} else if( this.data.invoice_type == 12 ) {
					return 'Yearly <span class="subdue">[' + this.data['status_text'] + ']</span>';
				} else if( this.data.invoice_type == 90 ) {
					return this.data[i];
				} 
				return this.data[i] + ' <span class="subdue">[' + this.data['status_text'] + ']</span>';
			}
//			if( i == 'po_number' && this.data.status < 50 ) {
//				// Check if salesrep and if have access to update po_number
//				if( M.curBusiness.permissions.owners != null 
//					|| M.curBusiness.permissions.employees != null
//					|| (M.userPerms&0x01) > 0
//					|| (M.curBusiness.permissions.salesreps != null 
//						&& M.curBusiness.sapos.settings['rules-salesreps-invoice-po_number'] != null
//						&& M.curBusiness.sapos.settings['rules-salesreps-invoice-po_number'] == 'edit'
//						&& this.data.status < 20
//						)
//					) {
//				return this.data[i] + " <button onclick=\"event.stopPropagation(); M.ciniki_sapos_invoice.updateInvoiceField(\'po_number\',\'PO Number\'); return false;\">Edit</button>";
//				} 
//				return this.data[i];
//			}
			return this.data[i];
		};
		this.invoice.listFn = function(s, i, d) {
			if( (s == 'details' || s == 'billing' || s == 'shipping' || s == 'work' )
				&& this.rightbuttons['edit'] != null ) {
				return 'M.ciniki_sapos_invoice.editInvoice(\'M.ciniki_sapos_invoice.showInvoice();\',M.ciniki_sapos_invoice.invoice.invoice_id);';
			}
		};
		this.invoice.rowStyle = function(s, i, d) {
			// if shipping enabled
			if( s == 'shipitems' && (M.curBusiness.modules['ciniki.sapos'].flags&0x40) > 0 ) {
				if( (d.item.flags&0x0300) > 0 ) {
					return 'background: ' + M.ciniki_sapos_invoice.colours['invoice-item-forced-backordered'] + ';';
				} else if( d.item.required_quantity != null && d.item.required_quantity == 0 ) {
					return 'background: ' + M.ciniki_sapos_invoice.colours['invoice-item-fulfilled'] + ';';
				} else if( d.item.required_quantity > 0 && d.item.inventory_quantity > 0 && d.item.inventory_quantity < d.item.required_quantity) {
					return 'background: ' + M.ciniki_sapos_invoice.colours['invoice-item-partial'] + ';';
				} else if( d.item.required_quantity > 0 && d.item.inventory_quantity > 0 && d.item.inventory_reserved != null && d.item.inventory_reserved > 0 && (d.item.inventory_quantity-d.item.inventory_reserved) < d.item.required_quantity) {
					return 'background: ' + M.ciniki_sapos_invoice.colours['invoice-item-partial'] + ';';
				} else if( d.item.required_quantity > 0 && d.item.inventory_quantity > 0 ) {
					return 'background: ' + M.ciniki_sapos_invoice.colours['invoice-item-available'] + ';';
				} else if( d.item.required_quantity > 0 && d.item.inventory_quantity == 0 ) {
					return 'background: ' + M.ciniki_sapos_invoice.colours['invoice-item-backordered'] + ';';
				}
			}
			return '';
		};
//		this.invoice.cellStyle = function(s, i, j, d) {
//			if( s == 'items' && j == 1 && d.item.required_quantity > 0 && d.item.required_quantity > d.item.inventory_quantity ) {
//				return 'background: ' + M.ciniki_sapos_invoice.colours['invoice-item-backordered'] + ';'; 
//			}
//			return '';
//		};
		this.invoice.cellValue = function(s, i, j, d) {
			if( s == 'customer_details' ) {
				switch (j) {
					case 0: return d.detail.label;
					case 1: return (d.detail.label == 'Email'?M.linkEmail(d.detail.value):d.detail.value);
				}
			}
			if( s == 'shipitems' ) {
				if( j == 0 ) {
					if( d.item.code != null && d.item.code != '' ) {
						return '<span class="maintext">' + d.item.code + '</span><span class="subtext">' + d.item.description + '</span>' + (d.item.notes!=null&&d.item.notes!=''?'<span class="subsubtext">'+d.item.notes+'</span>':'');
					}
					if( d.item.notes != null && d.item.notes != '' ) {
						return '<span class="maintext">' + d.item.description + '</span><span class="subtext">' + d.item.notes + '</span>';
					}
					return d.item.description;
				}
				if( j == 1 ) {
					return d.item.quantity;
				}
				if( j == 2 ) {
					if( M.curBusiness.permissions.owners == null
						&& M.curBusiness.permissions.employees == null 
						&& M.curBusiness.permissions.salesreps != null 
						) {
						if( d.item.inventory_reserved != null ) {
							return (d.item.inventory_quantity - d.item.inventory_reserved);
						} 
						return d.item.inventory_quantity;
					} else {
						return d.item.inventory_quantity + (d.item.inventory_reserved!=null?' [' + d.item.inventory_reserved + ']':'');
					}

				}
				if( j == 3 ) {
					return d.item.required_quantity;
				}
				if( j == 4 ) {
					return d.item.shipped_quantity;
				}
				if( j == 5 ) {
					var discount = '';
					if( d.item.discount_amount != 0) {
						if( d.item.unit_discount_amount > 0 ) {
//							discount += '-' + ((d.item.quantity>0&&d.item.quantity!=1)?(d.item.quantity+'@'):'') + '$' + d.item.unit_discount_amount;
							discount += '-' + d.item.unit_discount_amount_display + ((d.item.quantity>0&&d.item.quantity!=1)?('x'+d.item.quantity):'');
						}
						if( d.item.unit_discount_percentage > 0 ) {
							if( discount != '' ) { discount += ', '; }
							discount += '-' + d.item.unit_discount_percentage + '%';
						}
					}
					if( (this.data.flags&0x01) > 0 ) {
//						return ((d.item.quantity>0&&d.item.quantity!=1)?(d.item.quantity+' @ '):'') + d.item.unit_discounted_amount_display;
//						return d.item.quantity+' @ ' + d.item.unit_discounted_amount_display;
						return d.item.unit_discounted_amount_display;
					} else if( discount != '' ) {
//						return '<span class="maintext">' + ((d.item.quantity>0&&d.item.quantity!=1)?(d.item.quantity+' @ '):'') + d.item.unit_amount_display + '</span><span class="subtext">' + discount + ' (-' + d.item.discount_amount_display + ')</span>';
//						return '<span class="maintext">' + d.item.quantity+' @ ' + d.item.unit_amount_display + '</span><span class="subtext">' + discount + ' (-' + d.item.discount_amount_display + ')</span>';
						return '<span class="maintext">' + d.item.unit_amount_display + '</span><span class="subtext">' + discount + ' (-' + d.item.discount_amount_display + ')</span>';
					} else {
//						return ((d.item.quantity>0&&d.item.quantity!=1)?(d.item.quantity+' @ '):'') + d.item.unit_amount_display;
//						return d.item.quantity + ' @ ' + d.item.unit_amount_display;
						return d.item.unit_amount_display;
					}
				}
				if( j == 6 ) {
					return '<span class="maintext">' + d.item.total_amount_display + '</span><span class="subtext">' + ((d.item.taxtype_name!=null)?d.item.taxtype_name:'') + '</span>';
				}
			}
			if( s == 'items' ) {
				if( j == 0 ) {
					if( d.item.code != null && d.item.code != '' ) {
						return '<span class="maintext">' + d.item.code + '</span><span class="subtext">' + d.item.description + '</span>' + (d.item.notes!=null&&d.item.notes!=''?'<span class="subsubtext">'+d.item.notes+'</span>':'');
					}
					if( d.item.notes != null && d.item.notes != '' ) {
						return '<span class="maintext">' + d.item.description + '</span><span class="subtext">' + d.item.notes + '</span>';
					}
					return d.item.description;
				}
				if( j == 1 ) {
					var discount = '';
					if( d.item.discount_amount != 0) {
						if( d.item.unit_discount_amount > 0 ) {
							discount += '-' + ((d.item.quantity>0&&d.item.quantity!=1)?(d.item.quantity+'@'):'') + '$' + d.item.unit_discount_amount;
						}
						if( d.item.unit_discount_percentage > 0 ) {
							if( discount != '' ) { discount += ', '; }
							discount += '-' + d.item.unit_discount_percentage + '%';
						}
					}
					if( (this.data.flags&0x01) > 0 ) {
						return ((d.item.quantity>0&&d.item.quantity!=1)?(d.item.quantity+' @ '):'') + d.item.unit_discounted_amount_display;
					} else if( discount != '' ) {
						return '<span class="maintext">' + ((d.item.quantity>0&&d.item.quantity!=1)?(d.item.quantity+' @ '):'') + d.item.unit_amount_display + '</span><span class="subtext">' + discount + ' (-' + d.item.discount_amount_display + ')</span>';
					} else {
						return ((d.item.quantity>0&&d.item.quantity!=1)?(d.item.quantity+' @ '):'') + d.item.unit_amount_display;
					}
				}
				if( j == 2 ) {
					return '<span class="maintext">' + d.item.total_amount_display + '</span><span class="subtext">' + ((d.item.taxtype_name!=null)?d.item.taxtype_name:'') + '</span>';
				}
			}
			if( s == 'tallies' ) {
				switch(j) {
					case 0: return d.tally.description;
					case 1: return d.tally.amount;
				}
			}
			if( s == 'transactions' ) {
				switch(j) {
					case 0: return d.transaction.transaction_type_text + ((d.transaction.source_text!=null&&d.transaction.source_text!='')?(' - ' + d.transaction.source_text):'');
					case 1: return d.transaction.transaction_date;
					case 2: return ((d.transaction.transaction_type==60)?'-':'')+d.transaction.customer_amount;
//					case 3: return d.transaction.transaction_fees;
//					case 4: return d.transaction.business_amount;
				}
			}
			if( s == 'shipments' ) {
				switch(j) {
					case 0: return d.shipment.shipment_number;
					case 1: return d.shipment.pack_date;
					case 2: return d.shipment.ship_date;
					case 3: return d.shipment.status_text;
				}
			}
		};
//		this.invoice.cellClass = function(s, i, j, d) {
//			if( s == 'items' && j >= 2) { return 'alignright'; }
//			if( s == 'tallies' ) { return 'alignright'; }
//			if( s == 'transactions' ) { return 'alignright'; }
//			return '';
//		};
		this.invoice.rowFn = function(s, i, d) {
			if( s == 'customer_details' ) { return ''; }
			if( s == 'shipitems' && M.ciniki_sapos_invoice.invoice.data.status < 50 ) {
				return 'M.ciniki_sapos_invoice.editItem(\'M.ciniki_sapos_invoice.showInvoice();\',\'' + d.item.id + '\');';
			}
			if( s == 'items' && M.ciniki_sapos_invoice.invoice.data.status < 50 ) {
				return 'M.ciniki_sapos_invoice.editItem(\'M.ciniki_sapos_invoice.showInvoice();\',\'' + d.item.id + '\');';
			}
			if( s == 'tallies' ) {
				return '';
			}
			if( s == 'transactions' ) {
				if( d.transaction.id > 0 ) {
					return 'M.ciniki_sapos_invoice.editTransaction(\'M.ciniki_sapos_invoice.showInvoice();\',\'' + d.transaction.id + '\',0);';
				} 
				return '';
//				return 'M.startApp(\'ciniki.sapos.transactions\',null,\'M.ciniki_sapos_invoice.showInvoice();\',\'mc\',{\'transaction_id\':\'' + d.transaction.id + '\'});';
			}
			if( s == 'shipments' ) {
				return 'M.startApp(\'ciniki.sapos.shipment\',null,\'M.ciniki_sapos_invoice.showInvoice();\',\'mc\',{\'shipment_id\':\'' + d.shipment.id + '\'});';
			}
			return '';
		};
		this.invoice.prevButtonFn = function() {
			if( this.prevnext.prev_id > 0 ) {
				return 'M.ciniki_sapos_invoice.showInvoice(null,\'' + this.prevnext.prev_id + '\');'
			}
		};
		this.invoice.nextButtonFn = function() {
			if( this.prevnext.next_id > 0 ) {
				return 'M.ciniki_sapos_invoice.showInvoice(null,\'' + this.prevnext.next_id + '\');'
			}
		};
//		this.invoice.addButton('edit', 'Edit', 'M.ciniki_sapos_invoice.editInvoice(\'M.ciniki_sapos_invoice.showInvoice();\',M.ciniki_sapos_invoice.invoice.invoice_id);');
//		this.invoice.addButton('add', 'Invoice', 'M.ciniki_sapos_invoice.createInvoice(M.ciniki_sapos_invoice.invoice.cb,0,null);');
		this.invoice.addButton('next', 'Next');
		this.invoice.addClose('Back');
		this.invoice.addLeftButton('prev', 'Prev');

		//
		// The edit invoice panel
		//
		this.email = new M.panel('Email Invoice',
			'ciniki_sapos_invoice', 'email',
			'mc', 'medium', 'sectioned', 'ciniki.sapos.invoice.email');
		this.email.invoice_id = 0;
		this.email.data = {};
		this.email.sections = {
			'_subject':{'label':'', 'fields':{
				'subject':{'label':'Subject', 'type':'text', 'history':'no'},
				}},
			'_textmsg':{'label':'Message', 'fields':{
				'textmsg':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'large', 'history':'no'},
				}},
			'_buttons':{'label':'', 'buttons':{
				'send':{'label':'Send', 'fn':'M.ciniki_sapos_invoice.sendEmail();'},
				}},
		};
		this.email.fieldValue = function(s, i, d) {
			return this.data[i];
		};
		this.email.addClose('Cancel');


		//
		// The edit invoice panel
		//
		this.edit = new M.panel('Invoice',
			'ciniki_sapos_invoice', 'edit',
			'mc', 'medium mediumaside', 'sectioned', 'ciniki.sapos.invoice.edit');
		this.edit.invoice_id = 0;
		this.edit.data = {};
		this.edit.sections = {
			'details':{'label':'', 'aside':'yes', 'fields':{
				'invoice_type':{'label':'Type', 'active':'yes', 'type':'toggle', 'default':'invoice', 'toggles':M.ciniki_sapos_invoice.invoiceTypes, 'fn':'M.ciniki_sapos_invoice.edit.updateForm();'},
				'invoice_number':{'label':'Invoice #', 'type':'text', 'size':'small'},
				'po_number':{'label':'PO #', 'autofocus':'yes', 'type':'text', 'size':'medium'},
				'salesrep_id':{'label':'Sales Rep', 'active':'no', 'type':'select', 'options':{}},
				'status':{'label':'Status', 'type':'select', 'options':M.ciniki_sapos_invoice.invoiceStatuses},
				'payment_status':{'label':'Payment', 'active':'yes', 'type':'select', 'options':M.ciniki_sapos_invoice.paymentStatuses},
				'shipping_status':{'label':'Shipping', 'active':'yes', 'type':'select', 'options':M.ciniki_sapos_invoice.shippingStatuses},
				'manufacturing_status':{'label':'Manufacturing', 'active':'yes', 'type':'select', 'options':M.ciniki_sapos_invoice.manufacturingStatuses},
				'invoice_date':{'label':'Date', 'type':'text', 'size':'medium'},
				'due_date':{'label':'Due Date', 'type':'text', 'size':'medium'},
				'flags':{'label':'Options', 'type':'flags', 'flags':this.invoiceFlags},
				'tax_location_id':{'label':'Tax Location', 'active':'no', 'type':'select', 'options':{}},
				'pricepoint_id':{'label':'Pricepoint', 'active':'no', 'type':'select', 'options':{}},
				}},
			'_customer_notes':{'label':'Customer Notes', 'aside':'yes', 'fields':{
				'customer_notes':{'label':'', 'hidelabel':'', 'type':'textarea', 'size':'small'},
				}},
			'_internal_notes':{'label':'Internal Notes', 'aside':'yes', 'fields':{
				'internal_notes':{'label':'', 'hidelabel':'', 'type':'textarea', 'size':'small'},
				}},
			'shipping':{'label':'Shipping Address', 'active':'no', 'fields':{
				'shipping_name':{'label':'Name', 'type':'text'},
				'shipping_address1':{'label':'Street', 'type':'text'},
				'shipping_address2':{'label':'', 'type':'text'},
				'shipping_city':{'label':'City', 'type':'text'},
				'shipping_province':{'label':'Province/State', 'type':'text'},
				'shipping_postal':{'label':'Postal/Zip', 'type':'text'},
				'shipping_country':{'label':'Country', 'type':'text'},
				'shipping_phone':{'label':'Phone', 'type':'text', 'hint':'Helpful for deliveries'},
				}},
			'billing':{'label':'Billing Address', 'fields':{
				'billing_name':{'label':'Name', 'type':'text'},
				'billing_address1':{'label':'Street', 'type':'text'},
				'billing_address2':{'label':'', 'type':'text'},
				'billing_city':{'label':'City', 'type':'text'},
				'billing_province':{'label':'Province/State', 'type':'text'},
				'billing_postal':{'label':'Postal/Zip', 'type':'text'},
				'billing_country':{'label':'Country', 'type':'text'},
				}},
			'work':{'label':'Work Location', 'fields':{
				'work_address1':{'label':'Street', 'type':'text'},
				'work_address2':{'label':'', 'type':'text'},
				'work_city':{'label':'City', 'type':'text'},
				'work_province':{'label':'Province/State', 'type':'text'},
				'work_postal':{'label':'Postal/Zip', 'type':'text'},
				'work_country':{'label':'Country', 'type':'text'},
				}},
			'_buttons':{'label':'', 'buttons':{
				'save':{'label':'Save', 'fn':'M.ciniki_sapos_invoice.saveInvoice();'},
				}},
		};
		this.edit.fieldValue = function(s, i, d) {
			return this.data[i];
		};
		this.edit.fieldHistoryArgs = function(s, i) {
			return {'method':'ciniki.sapos.history', 'args':{'business_id':M.curBusinessID,
				'object':'ciniki.sapos.invoice', 'object_id':this.invoice_id, 'field':i}};
		};
		this.edit.updateForm = function() {
			var t = this.formFieldValue(this.sections.details.fields.invoice_type, 'invoice_type');
			var e_po = M.gE(this.panelUID + '_po_number');
			var e_is = M.gE(this.panelUID + '_status');
			var e_ps = M.gE(this.panelUID + '_payment_status');
			var e_ss = M.gE(this.panelUID + '_shipping_status');
			var e_ms = M.gE(this.panelUID + '_manufacturing_status');
			var e_dd = M.gE(this.panelUID + '_due_date');
			if( t == '90' ) {
				if( e_po != null ) { e_po.parentNode.parentNode.style.display = 'none'; }
				if( e_is != null ) { e_is.parentNode.parentNode.style.display = 'none'; }
				if( e_ps != null ) { e_ps.parentNode.parentNode.style.display = 'none'; }
				if( e_ss != null ) { e_ss.parentNode.parentNode.style.display = 'none'; }
				if( e_ms != null ) { e_ms.parentNode.parentNode.style.display = 'none'; }
				if( e_dd != null ) { e_dd.parentNode.parentNode.style.display = 'none'; }
				this.sections.details.fields.po_number.visible = 'no';
				this.sections.details.fields.status.visible = 'no';
				this.sections.details.fields.payment_status.visible = 'no';
				this.sections.details.fields.shipping_status.visible = 'no';
				this.sections.details.fields.manufacturing_status.visible = 'no';
				this.sections.details.fields.due_date.visible = 'no';
			} else {
				this.sections.details.fields.po_number.visible = 'yes';
				this.sections.details.fields.status.visible = 'yes';
				this.sections.details.fields.payment_status.visible = ((M.curBusiness.modules['ciniki.sapos'].flags&0x0200)>0||this.data.payment_status>0)?'yes':'no';
				this.sections.details.fields.shipping_status.visible = ((M.curBusiness.modules['ciniki.sapos'].flags&0x40)>0||this.data.shipping_status>0)?'yes':'no';
				this.sections.details.fields.manufacturing_status.visible = ((M.curBusiness.modules['ciniki.sapos'].flags&0x80)>0||this.data.manufacturing_status>0)?'yes':'no';
				this.sections.details.fields.due_date.visible = 'yes';
				if( e_po != null && this.sections.details.fields.po_number.visible == 'yes' ) {
					e_po.parentNode.parentNode.style.display = 'table-row';
				}
				if( e_is != null && this.sections.details.fields.status.visible == 'yes' ) {
					e_is.parentNode.parentNode.style.display = 'table-row';
				}
				if( e_ps != null && this.sections.details.fields.payment_status.visible == 'yes' ) {
					e_ps.parentNode.parentNode.style.display = 'table-row';
				}
				if( e_ss != null && this.sections.details.fields.shipping_status.visible == 'yes' ) {
					e_ss.parentNode.parentNode.style.display = 'table-row';
				}
				if( e_ms != null && this.sections.details.fields.manufacturing_status.visible == 'yes' ) {
					e_ms.parentNode.parentNode.style.display = 'table-row';
				}
				if( e_dd != null && this.sections.details.fields.due_date.visible == 'yes' ) {
					e_dd.parentNode.parentNode.style.display = 'table-row';
				}
			}
			return true;
		};
		this.edit.addButton('save', 'Save', 'M.ciniki_sapos_invoice.saveInvoice();');
		this.edit.addClose('Cancel');

		//
		// The item add/edit panel
		//
		this.item = new M.panel('Invoice Item',
			'ciniki_sapos_invoice', 'item',
			'mc', 'medium', 'sectioned', 'ciniki.sapos.invoice.item');
		this.item.item_id = 0;
//		this.item.pricepoint_id = 0;
		this.item.object = '';
		this.item.object_id = 0;
		this.item.price_id = 0;
		this.item.data = {};
		this.item.sections = {
			'details':{'label':'', 'fields':{
				'code':{'label':'Code', 'type':'text', 'livesearch':'yes', 
					'livesearchcols':3,
					},
				'description':{'label':'Description', 'type':'text', 'livesearch':'yes', 
					'livesearchcols':3,
					},
//				'description':{'label':'Description', 'type':'text', 'livesearch':'yes'},
				'quantity':{'label':'Quantity', 'type':'text', 'size':'small'},
				'unit_amount':{'label':'Price', 'type':'text', 'size':'small'},
				'unit_discount_amount':{'label':'Discount Amount', 'type':'text', 'size':'small'},
				'unit_discount_percentage':{'label':'Discount %', 'type':'text', 'size':'small'},
				'taxtype_id':{'label':'Taxes', 'type':'select', 'options':{}},
				'force_backorder':{'label':'Backorder', 'active':'no', 'type':'toggle', 'default':'no', 'toggles':{'no':'No', 'yes':'Yes'}},
				}},
			'_notes':{'label':'Notes', 'fields':{
				'notes':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'small'},
				}},
			'_buttons':{'label':'', 'buttons':{
				'save':{'label':'Save', 'fn':'M.ciniki_sapos_invoice.saveItem();'},
				'delete':{'label':'Delete', 'fn':'M.ciniki_sapos_invoice.deleteItem(M.ciniki_sapos_invoice.item.item_id);'},
				}},
			};
		this.item.liveSearchCb = function(s, i, v) {
			if( i == 'code' || i == 'description' ) {
				M.api.getJSONBgCb('ciniki.sapos.invoiceItemSearch', {'business_id':M.curBusinessID,
					'field':i, 'pricepoint_id':M.ciniki_sapos_invoice.invoice.pricepoint_id, 'invoice_id':M.ciniki_sapos_invoice.invoice.invoice_id, 'start_needle':v, 'limit':15}, function(rsp) {
						M.ciniki_sapos_invoice.item.liveSearchShow(s,i,M.gE(M.ciniki_sapos_invoice.item.panelUID + '_' + i), rsp.items);
					});
			}
		};
		this.item.liveSearchResultClass = function(s,f,i,j,d) {
			if( d.item.price_description != null && d.item.price_description != '' 
				&& this.sections[s].fields[f].livesearchcols-j == 1 ) {
				return 'multiline';
			}
		};
		this.item.liveSearchResultValue = function(s,f,i,j,d) {
			if( j == 0 ) {
				if( (f == 'code' || f == 'description') && d.item != null ) { 
					if( d.item.code != null && d.item.code != '' ) {
						return d.item.code + ' - ' + d.item.description;
					}
					return d.item.description; 
				}
			}
			if( j == 1 && this.sections[s].fields[f].livesearchcols == 3 ) {
				if( d.item.inventory_available != null ) { return d.item.inventory_available; }
			}
			if( this.sections[s].fields[f].livesearchcols-j == 1 ) {
				if( d.item.price_description != null && d.item.price_description != '' ) {
					return '<span class="maintext">' + d.item.unit_amount + '</span><span class="subtext">' + d.item.price_description + '</span>';
				}
				return d.item.unit_amount;
			}
			return '';
		};
		this.item.liveSearchResultRowStyle = function(s,f,i,d) {
			if( d.item.inventory_available <= 0 ) {
				return 'background: ' + M.ciniki_sapos_invoice.colours['invoice-item-backordered'] + ';';
			}
			return '';
		};
		this.item.liveSearchResultRowFn = function(s,f,i,j,d) {
			if( (f == 'code' || f == 'description') && d.item != null ) {
				return 'M.ciniki_sapos_invoice.item.updateFromSearch(\'' + s + '\',\'' + f + '\',\'' + d.item.object + '\',\'' + d.item.object_id + '\',\'' + escape(d.item.code!=null?d.item.code:'') + '\',\'' + escape(d.item.description) + '\',\'' + d.item.quantity + '\',\'' + escape(d.item.unit_amount) + '\',\'' + escape(d.item.unit_discount_amount) + '\',\'' + escape(d.item.unit_discount_percentage) + '\',\'' + d.item.taxtype_id + '\',\'' + d.item.price_id + '\');';
			}
		};

		this.item.updateFromSearch = function(s, fid, o, oid, c, d, q, u, uda, udp, t, pid) {
			this.object = o;
			this.object_id = oid;
			if( this.sections.details.fields.code.active == 'yes' ) {
				this.setFieldValue('code', unescape(c));
			}
			this.setFieldValue('description', unescape(d));
			// Only update quantity if nothing specified yet
			if( this.formFieldValue(this.sections.details.fields.quantity, 'quantity') == '' ) {
				this.setFieldValue('quantity', q);
			}
			this.setFieldValue('unit_amount', unescape(u));
			this.setFieldValue('unit_discount_amount', unescape(uda));
			this.setFieldValue('unit_discount_percentage', unescape(udp));
			if( M.curBusiness.modules['ciniki.taxes'] != null ) {
				this.setFieldValue('taxtype_id', t);
			}
			this.price_id = pid;
			this.removeLiveSearch(s, fid);
		};
		this.item.fieldValue = function(s, i, d) {
			if( this.data != null && this.data[i] != null ) { return this.data[i]; }
			return '';
		};
		this.item.fieldHistoryArgs = function(s, i) {
			return {'method':'ciniki.sapos.history', 'args':{'business_id':M.curBusinessID,
				'object':'ciniki.sapos.invoice_item', 'object_id':this.item_id, 'field':i}};
		};
		this.item.addButton('save', 'Save', 'M.ciniki_sapos_invoice.saveItem();');
		this.item.addClose('Cancel');

		//
		// The transaction panel
		//
		this.transaction = new M.panel('Transaction',
			'ciniki_sapos_invoice', 'transaction',
			'mc', 'medium', 'sectioned', 'ciniki.sapos.invoice.transaction');
		this.transaction.transaction_id = 0;
		this.transaction.data = {};
		this.transaction.sections = {
			'details':{'label':'', 'fields':{
				'transaction_type':{'label':'Type', 'type':'toggle', 'default':'20', 'toggles':M.ciniki_sapos_invoice.transactionTypes},
				'transaction_date':{'label':'Date', 'type':'text', 'size':'medium'},
				'source':{'label':'Source', 'type':'select', 'options':M.ciniki_sapos_invoice.transactionSources},
				'customer_amount':{'label':'Customer Amount', 'type':'text', 'size':'small'},
				'transaction_fees':{'label':'Fees', 'type':'text', 'size':'small'},
				'business_amount':{'label':'Business Amount', 'type':'text', 'size':'small'},
				}},
			'_notes':{'label':'Notes', 'fields':{
				'notes':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'small'},
				}},
			'_buttons':{'label':'', 'buttons':{
				'save':{'label':'Save', 'fn':'M.ciniki_sapos_invoice.saveTransaction();'},
				'delete':{'label':'Delete', 'fn':'M.ciniki_sapos_invoice.deleteTransaction(M.ciniki_sapos_invoice.transaction.transaction_id);'},
				}},
		};
		this.transaction.fieldValue = function(s, i, d) {
			if( this.data != null && this.data[i] != null ) { return this.data[i]; }
			return '';
		};
		this.transaction.fieldHistoryArgs = function(s, i) {
			return {'method':'ciniki.sapos.history', 'args':{'business_id':M.curBusinessID,
				'object':'ciniki.sapos.transaction', 'object_id':this.transaction_id, 'field':i}};
		};
		this.transaction.addButton('save', 'Save', 'M.ciniki_sapos_invoice.saveTransaction();');
		this.transaction.addClose('Cancel');
	}; 

	this.start = function(cb, aP, aG) {
		args = {};
		if( aG != null ) { args = eval(aG); }
		var aC = M.createContainer(aP, 'ciniki_sapos_invoice', 'yes');
		if( aC == null ) {
			alert('App Error');
			return false;
		}

		//
		// Setup editable fields for owners/employees
		//
		if( M.curBusiness.permissions.owners != null
			|| M.curBusiness.permissions.employees != null
			|| (M.userPerms&0x01) > 0 
			) {
			for(i in this.edit.sections.details.fields) {
				this.edit.sections.details.fields[i].active = 'yes';
			}
			this.edit.sections.billing.active = 'yes';
			this.edit.sections.shipping.active = 'yes';
			this.edit.sections.work.active = 'yes';
			this.edit.sections._customer_notes.active = 'yes';
			this.edit.sections._internal_notes.active = 'yes';
		}

		// Change also in shipment.js
		this.colours = {
			'invoice-item-available':'#C0FFC0',
			'invoice-item-partial':'#FFFFC0',
			'invoice-item-backordered':'#FFC6C6',
			'invoice-item-forced-backordered':'#FFC6C6',
			'invoice-item-fulfilled':'#E6E6E6',
		};
		for(i in this.colours) {
			if( M.curBusiness.sapos.settings['ui-colours-' + i] != null ) {
				this.colours[i] = M.curBusiness.sapos.settings['ui-colours-' + i];
			}
		}
		this.invoiceStatuses = {
			'10':'Entered',
			};
		this.orderStatuses = {
			'10':'Incomplete',
			'15':'On Hold',
			};
		if( (M.curBusiness.modules['ciniki.sapos'].flags&0x80) > 0 ) {
			this.invoiceStatuses['20'] = 'Pending Manufacturing';
			this.orderStatuses['20'] = 'Pending Manufacturing';
		}
		if( (M.curBusiness.modules['ciniki.sapos'].flags&0x40) > 0 ) {
			this.invoiceStatuses['30'] = 'Pending Shipping';
			this.orderStatuses['30'] = 'Pending Shipping';
		}
		if( (M.curBusiness.modules['ciniki.sapos'].flags&0x0200) > 0 ) {
			this.invoiceStatuses['40'] = 'Payment Required';
			this.orderStatuses['40'] = 'Payment Required';
		}
		this.invoiceStatuses['50'] = 'Paid';
		this.invoiceStatuses['55'] = 'Refunded';
		this.invoiceStatuses['60'] = 'Void';
		this.orderStatuses['50'] = 'Fulfilled';
		this.orderStatuses['55'] = 'Refunded';
		this.orderStatuses['60'] = 'Void';

		this.edit.sections.details.fields.status.options = this.invoiceStatuses;
		this.edit.sections.shipping.active = ((M.curBusiness.modules['ciniki.sapos'].flags&0x40)>0)?'yes':'no';
		this.edit.sections.work.active = ((M.curBusiness.modules['ciniki.sapos'].flags&0x020000)>0)?'yes':'no';

		//
		// Determine what types we have available
		//
		this.invoiceTypes = {};
		var ct = 0;
		this.default_invoice_type = '';
		if( (M.curBusiness.modules['ciniki.sapos'].flags&0x01) > 0 ) {
			this.invoiceTypes['10'] = 'Invoice';
			if( this.default_invoice_type == '' ) { this.default_invoice_type = '10'; }
			ct++;
			if( (M.curBusiness.modules['ciniki.sapos'].flags&0x1000) > 0 ) {
				this.invoiceTypes['11'] = 'Monthly';
				ct++;
				this.invoiceTypes['12'] = 'Yearly';
				ct++;
			}
		}
		if( (M.curBusiness.modules['ciniki.sapos'].flags&0x20) > 0 ) {
			this.invoiceTypes['40'] = 'Order';
			if( this.default_invoice_type == '' ) { this.default_invoice_type = '40'; }
			ct++;
		}
		if( (M.curBusiness.modules['ciniki.sapos'].flags&0x10) > 0 ) {
			this.invoiceTypes['30'] = 'POS';
			if( this.default_invoice_type == '' ) { this.default_invoice_type = '30'; }
			ct++;
		}
		if( (M.curBusiness.modules['ciniki.sapos'].flags&0x08) > 0 ) {
			this.invoiceTypes['20'] = 'Cart';
			if( this.default_invoice_type == '' ) { this.default_invoice_type = '20'; }
			ct++;
		}
		if( (M.curBusiness.modules['ciniki.sapos'].flags&0x010000) > 0 ) {
			this.invoiceTypes['90'] = 'Quote';
			if( this.default_invoice_type == '' ) { this.default_invoice_type = '90'; }
			ct++;
		}
		if( ct == 1 ) {
//			this.invoice.sections.details.list.invoice_type_text.visible = 'no';
			this.edit.sections.details.fields.invoice_type.active = 'no';
		} else {
//			this.invoice.sections.details.list.invoice_type_text.visible = 'yes';
			this.edit.sections.details.fields.invoice_type.active = 'yes';
		}
		this.edit.sections.details.fields.invoice_type.toggles = this.invoiceTypes;
		this.item.sections.details.fields.code.active = ((M.curBusiness.modules['ciniki.sapos'].flags&0x0400)>0?'yes':'no');

		//
		// Setup the taxtypes available for the business
		//
		if( M.curBusiness.modules['ciniki.taxes'] != null ) {
			this.item.sections.details.fields.taxtype_id.active = 'yes';
			this.item.sections.details.fields.taxtype_id.options = {'0':'No Taxes'};
			if( M.curBusiness.taxes != null && M.curBusiness.taxes.settings.types != null ) {
				for(i in M.curBusiness.taxes.settings.types) {
					this.item.sections.details.fields.taxtype_id.options[M.curBusiness.taxes.settings.types[i].type.id] = M.curBusiness.taxes.settings.types[i].type.name;
				}
			}
			//
			// Setup the tax locations
			//
			if( (M.curBusiness.modules['ciniki.taxes'].flags&0x01) > 0 
				&& M.curBusiness.taxes.settings != null
				&& M.curBusiness.taxes.settings.locations != null
				) {
				var locations = {'0':'Use Shipping Address'};
				var locs = M.curBusiness.taxes.settings.locations;
				for(i in locs) {
					locations[locs[i].location.id] = locs[i].location.name + ' [' + (locs[i].location.rates!=null?locs[i].location.rates:'None') + ']';
				}
				this.edit.sections.details.fields.tax_location_id.active = 'yes';
				this.edit.sections.details.fields.tax_location_id.options = locations;
			} else {
				this.edit.sections.details.fields.tax_location_id.options = {};
				this.edit.sections.details.fields.tax_location_id.active = 'no';
			}
		} else {
			this.edit.sections.details.fields.tax_location_id.active = 'no';
			this.edit.sections.details.fields.tax_location_id.options = {};
			this.item.sections.details.fields.taxtype_id.active = 'no';
			this.item.sections.details.fields.taxtype_id.options = {'0':'No Taxes'};
		}

		if( M.curBusiness.modules['ciniki.products'] != null 
			&& (M.curBusiness.modules['ciniki.products'].flags&0x04) > 0 ) {
			this.item.sections.details.fields.code.livesearchcols=3;
			this.item.sections.details.fields.code.headerValues = ['Item', 'Available', 'Price'];
			this.item.sections.details.fields.description.livesearchcols=3;
			this.item.sections.details.fields.description.headerValues = ['Item', 'Available', 'Price'];
		} else {
			this.item.sections.details.fields.code.livesearchcols=2;
			this.item.sections.details.fields.code.headerValues = ['Item', 'Price'];
			this.item.sections.details.fields.description.livesearchcols=2;
			this.item.sections.details.fields.description.headerValues = ['Item', 'Price'];
		}

		if( (M.curBusiness.modules['ciniki.sapos'].flags&0x40) ) {
			this.invoice.sections.shipitems.active = 'yes';
			this.invoice.sections.items.active = 'no';
		} else {
			this.invoice.sections.shipitems.active = 'no';
			this.invoice.sections.items.active = 'yes';
		}
		// If salesreps are enabled
		if( (M.curBusiness.modules['ciniki.sapos'].flags&0x0800) > 0 ) {
			this.edit.sections.details.fields.salesrep_id.active = 'yes';
		} else {
			this.edit.sections.details.fields.salesrep_id.active = 'no';
		}

		if( M.curBusiness.modules['ciniki.customers'] != null
			&& (M.curBusiness.modules['ciniki.customers'].flags&0x1000) > 0 
			) {
			var pricepoints = {};
			var s_pp = M.curBusiness.customers.settings.pricepoints;
			pricepoints[0] = 'None';
			for(i in s_pp) {
				pricepoints[s_pp[i].pricepoint.id] = s_pp[i].pricepoint.name;
			}
			this.edit.sections.details.fields.pricepoint_id.active = 'yes';
			this.edit.sections.details.fields.pricepoint_id.options = pricepoints;
		} else {
			this.edit.sections.details.fields.pricepoint_id.active = 'no';
			this.edit.sections.details.fields.pricepoint_id.options = {};
		}

		if( M.curBusiness.permissions.owners == null
			&& M.curBusiness.permissions.employees == null 
			&& M.curBusiness.permissions.salesreps != null 
			) {
			var edit = 'no';
			for(i in this.edit.sections.details.fields) {
				if( (M.curBusiness.sapos.settings['rules-salesreps-invoice-' + i] != null
					&& M.curBusiness.sapos.settings['rules-salesreps-invoice-' + i] == 'edit') ) {
					this.edit.sections.details.fields[i].active = 'yes';
					edit = 'yes';
				} else {
					this.edit.sections.details.fields[i].active = 'no';
				}
			}
			if( (M.curBusiness.sapos.settings['rules-salesreps-invoice-billing'] != null
				&& M.curBusiness.sapos.settings['rules-salesreps-invoice-billing'] == 'edit') ) {
				this.edit.sections.billing.active = 'yes';
				edit = 'yes';
			} else {
				this.edit.sections.billing.active = 'no';
			}
			if( (M.curBusiness.sapos.settings['rules-salesreps-invoice-shipping'] != null
				&& M.curBusiness.sapos.settings['rules-salesreps-invoice-shipping'] == 'edit') ) {
				this.edit.sections.shipping.active = 'yes';
				edit = 'yes';
			} else {
				this.edit.sections.shipping.active = 'no';
			}
			if( (M.curBusiness.sapos.settings['rules-salesreps-invoice-work'] != null
				&& M.curBusiness.sapos.settings['rules-salesreps-invoice-work'] == 'edit') ) {
				this.edit.sections.work.active = 'yes';
				edit = 'yes';
			} else {
				this.edit.sections.work.active = 'no';
			}
			if( (M.curBusiness.sapos.settings['rules-salesreps-invoice-notes'] != null
				&& M.curBusiness.sapos.settings['rules-salesreps-invoice-notes'] == 'edit') ) {
				this.edit.sections._customer_notes.active = 'yes';
				this.edit.sections._internal_notes.active = 'yes';
				edit = 'yes';
			} else {
				this.edit.sections._customer_notes.active = 'no';
				this.edit.sections._internal_notes.active = 'no';
			}
		}

		this.item.pricepoint_id = 0;

		if( args.items != null && args.customer_id != null ) {
			// Create new invoice with item
			this.createInvoice(cb, args.customer_id, null, args.items, args.invoice_type);
		} else if( args.object != null && args.object_id != null && args.customer_id != null ) {
			// Create new invoice with this object/object_id
			this.createInvoice(cb, args.customer_id, [{'object':args.object,'id':args.object_id}]);
		} else if( args.object != null && args.object_id != null ) {
			// Create new invoice with this object/object_id
			this.createInvoice(cb, 0, [{'object':args.object,'id':args.object_id}]);
		} else if( args.customer_id != null && args.invoice_type != null ) {
			this.createInvoice(cb, args.customer_id, null, null, args.invoice_type);
		} else if( args.customer_id != null ) {
			// Create new invoice with just customer
			this.createInvoice(cb, args.customer_id, null, null, null);
		} else if( args.invoice_type != null ) {
			// Create new invoice with just customer
			this.createInvoice(cb, null, null, null, args.invoice_type);
		} else if( args.invoice_id != null ) {
			// Edit an existing invoice
			this.showInvoice(cb, args.invoice_id, (args.list!=null?args.list:[]));
		} else {
			// Add blank invoice
			this.createInvoice(cb, 0, null);
		}
	};

	this.createInvoice = function(cb, cid, objects, items, type) {
		var c = '';
		var cm = '';
		// Create the array of items to be added to the new invoice
		if( objects != null ) {
			for(i in objects) {
				c += cm + objects[i].object + ':' + objects[i].id;
				cm = ',';
			}
			c = 'objects=' + c + '&';
		}
		if( type != null ) {
			if( (M.curBusiness.modules['ciniki.sapos'].flags&0x01) > 0 && type == 10) {
				c += 'invoice_type=10&';
			} else if( (M.curBusiness.modules['ciniki.sapos'].flags&0x1000) > 0 && type == 11) {
				c += 'invoice_type=11&';
			} else if( (M.curBusiness.modules['ciniki.sapos'].flags&0x08) > 0 && type == 20) {
				c += 'invoice_type=20&';
			} else if( (M.curBusiness.modules['ciniki.sapos'].flags&0x10) > 0 && type == 30) {
				c += 'invoice_type=30&';
			} else if( (M.curBusiness.modules['ciniki.sapos'].flags&0x20) > 0 && type == 40) {
				c += 'invoice_type=40&';
			} else if( (M.curBusiness.modules['ciniki.sapos'].flags&0x010000) > 0 && type == 90) {
				c += 'invoice_type=90&';
			} else {
				c += 'invoice_type=' + this.default_invoice_type + '&';
			}
		} else {
			c += 'invoice_type=' + this.default_invoice_type + '&';
		}
		if( items != null ) {
			var json = '';
			cm = '';
			for(i in items) {
				var item = ''
				for(j in items[i]) {
					item += (item!=''?',':'') + '"' + j + '":"' + items[i][j] + '"';
				}
				json += '{' + item + '}';
			}
			c += 'items=' + encodeURIComponent('[' + json + ']');
		}
		// Create the new invoice, and then display it
		M.api.postJSONCb('ciniki.sapos.invoiceAdd', {'business_id':M.curBusinessID,
			'customer_id':cid}, c, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_sapos_invoice.invoice;
				p.invoice_id = rsp.invoice.id;
				M.ciniki_sapos_invoice.showInvoiceFinish(cb, rsp);
			});
	};

	this.showInvoice = function(cb, iid, list) {
		if( iid != null ) { this.invoice.invoice_id = iid; }
		if( list != null ) { this.invoice.prevnext.list = list; }
		M.api.getJSONCb('ciniki.sapos.invoiceGet', {'business_id':M.curBusinessID,
			'invoice_id':this.invoice.invoice_id}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_sapos_invoice.invoice;
				M.ciniki_sapos_invoice.showInvoiceFinish(cb, rsp);
			});
	};

	this.showInvoiceFinish = function(cb, rsp) {
		var p = this.invoice;
		p.data = rsp.invoice;
		// Setup prev/next buttons
		p.prevnext.prev_id = 0;
		p.prevnext.next_id = 0;
		if( p.prevnext.list != null ) {
			for(i in p.prevnext.list) {
				if( p.prevnext.next_id == -1 ) {
					p.prevnext.next_id = p.prevnext.list[i].invoice.id;
					break;
				} else if( p.prevnext.list[i].invoice.id == p.invoice_id ) {
					p.prevnext.next_id = -1;
				} else {
					p.prevnext.prev_id = p.prevnext.list[i].invoice.id;
				}
			}
		}

		p.pricepoint_id = 0;
		if( rsp.invoice.pricepoint_id != null && rsp.invoice.pricepoint_id > 0 ) {
			p.pricepoint_id = rsp.invoice.pricepoint_id;
		} else if( rsp.invoice.customer != null && rsp.invoice.customer.pricepoint_id > 0 ) {
			p.pricepoint_id = rsp.invoice.customer.pricepoint_id;
		}
		if( rsp.invoice.status < 50 
			&& (M.curBusiness.modules['ciniki.sapos'].flags&0x0200) > 0
			&& (M.curBusiness.modules['ciniki.sapos'].flags&0x1000) > 0 
			) {
			p.sections._buttons.buttons.record.visible='yes';
		} else {
			p.sections._buttons.buttons.record.visible='no';
		}
		p.data.flags_text = '';
		for(i in this.invoiceFlags) {
			if( (rsp.invoice.flags&Math.pow(2,i-1)) > 0 ) {
				p.data.flags_text += (p.data.flags_text!=''?', ':'') + this.invoiceFlags[i].name;
			}
		}
		this.edit.sections.details.fields.status.visible = 'yes';
		switch(rsp.invoice.invoice_type) {
			case '10': 
				this.invoice.title = 'Invoice';
				this.invoice.sections.details.list.invoice_number.label = 'Invoice #';
				this.invoice.sections.details.list.invoice_date.label = 'Invoice Date';
				this.invoice.sections._buttons.buttons.delete.label = 'Delete Invoice';
				this.invoice.sections.details.list.submitted_by.visible = 'no';
				this.edit.sections.details.fields.status.options = M.ciniki_sapos_invoice.invoiceStatuses;
				break;
			case '11': 
				this.invoice.title = 'Monthly Invoice';
				this.invoice.sections.details.list.invoice_number.label = 'Recurring';
				this.invoice.sections.details.list.invoice_date.label = 'Next Invoice Date';
				this.invoice.sections._buttons.buttons.delete.label = 'Delete Recurring Invoice';
				this.invoice.sections.details.list.submitted_by.visible = 'no';
				this.edit.sections.details.fields.status.options = M.ciniki_sapos_invoice.invoiceStatuses;
				break;
			case '12': 
				this.invoice.title = 'Yearly Invoice';
				this.invoice.sections.details.list.invoice_number.label = 'Recurring';
				this.invoice.sections.details.list.invoice_date.label = 'Next Invoice Date';
				this.invoice.sections._buttons.buttons.delete.label = 'Delete Recurring Invoice';
				this.invoice.sections.details.list.submitted_by.visible = 'no';
				this.edit.sections.details.fields.status.options = M.ciniki_sapos_invoice.invoiceStatuses;
				break;
			case '20': 
				this.invoice.title = 'Shopping Cart';
				this.invoice.sections.details.list.invoice_number.label = 'Cart #';
				this.invoice.sections.details.list.invoice_date.label = 'Cart Date';
				this.invoice.sections._buttons.buttons.delete.label = 'Delete Cart';
				this.invoice.sections.details.list.submitted_by.visible = 'no';
				this.edit.sections.details.fields.status.options = M.ciniki_sapos_invoice.orderStatuses;
				break;
			case '30': 
				this.invoice.title = 'POS';
				this.invoice.sections.details.list.invoice_number.label = 'POS #';
				this.invoice.sections.details.list.invoice_date.label = 'POS Date';
				this.invoice.sections._buttons.buttons.delete.label = 'Delete Sale';
				this.invoice.sections.details.list.submitted_by.visible = 'no';
				this.edit.sections.details.fields.status.options = M.ciniki_sapos_invoice.orderStatuses;
				break;
			case '40': 
				this.invoice.title = 'Order';
				this.invoice.sections.details.list.invoice_number.label = 'Order #';
				this.invoice.sections.details.list.invoice_date.label = 'Order Date';
				this.invoice.sections._buttons.buttons.delete.label = 'Delete Order';
				this.invoice.sections.details.list.submitted_by.visible = 'yes';
				this.edit.sections.details.fields.status.options = M.ciniki_sapos_invoice.orderStatuses;
				break;
			case '90': 
				this.invoice.title = 'Quote';
				this.invoice.sections.details.list.invoice_number.label = 'Quote #';
				this.invoice.sections.details.list.invoice_date.label = 'Quote Date';
				this.invoice.sections._buttons.buttons.delete.label = 'Delete Quote';
				this.invoice.sections.details.list.submitted_by.visible = 'no';
				this.edit.sections.details.fields.status.visible = 'no';
				break;
		}
		p.sections.details.list.due_date.visible=(rsp.invoice.due_date!='')?'yes':'no';
		p.sections.details.list.flags_text.visible=(rsp.invoice.flags>0)?'yes':'no';
		p.sections.details.list.po_number.visible=(rsp.invoice.po_number!='')?'yes':'no';
		p.sections.details.list.submitted_by.visible=(rsp.invoice.submitted_by!='')?'yes':'no';
		if( M.curBusiness.sapos.settings['rules-invoice-submit-require-po_number']
			&& M.curBusiness.sapos.settings['rules-invoice-submit-require-po_number'] == 'yes' ) {
			// Force visible if required field
			p.sections.details.list.po_number.visible = 'yes';
		}
		p.sections.customer_notes.visible=(rsp.invoice.customer_notes!='')?'yes':'no';
		p.sections.internal_notes.visible=(rsp.invoice.internal_notes!='')?'yes':'no';
		p.sections.details.list.salesrep_id_text.visible=(rsp.invoice.salesrep_id_text!=null&&rsp.invoice.salesrep_id_text!='')?'yes':'no';
		// Decide if pricepoint should be shown
		if( M.curBusiness.modules['ciniki.customers'] != null 
			&& (M.curBusiness.modules['ciniki.customers'].flags&0x1000) > 0 ) {
			if( rsp.invoice.pricepoint_id != null && rsp.invoice.pricepoint_id > 0 
				&& M.curBusiness.customers.settings != null
				&& M.curBusiness.customers.settings.pricepoints != null 
				) {
				for(i in M.curBusiness.customers.settings.pricepoints) {
					if( M.curBusiness.customers.settings.pricepoints[i].pricepoint.id == rsp.invoice.pricepoint_id ) {
						rsp.invoice.pricepoint_id_text = M.curBusiness.customers.settings.pricepoints[i].pricepoint.name;
						break;
					}
				}
			} else {
				rsp.invoice.pricepoint_id_text = 'None';
			}
			p.data.pricepoint_id_text = rsp.invoice.pricepoint_id_text;
		}
		p.sections.details.list.pricepoint_id_text.visible=(rsp.invoice.pricepoint_id_text!=null&&rsp.invoice.pricepoint_id_text!='')?'yes':'no';
		if( rsp.invoice.status < 50 ) {
//			p.sections.details.list.status_text.visible = 'yes';
			p.sections.details.list.payment_status_text.visible = (rsp.invoice.invoice_type<90&&rsp.invoice.payment_status>0)?'yes':'no';
			p.sections.details.list.shipping_status_text.visible = (rsp.invoice.invoice_type<90&&rsp.invoice.shipping_status>0)?'yes':'no';
			p.sections.details.list.manufacturing_status_text.visible = (rsp.invoice.invoice_type<90&&rsp.invoice.manufacturing_status>0)?'yes':'no';
			// Hide if only shipping status visible
			if( rsp.invoice.payment_status == 0 && rsp.invoice.manufacturing_status == 0 ) {
				p.sections.details.list.shipping_status_text.visible = 'no';
			}
		} else {
//			p.sections.details.list.status_text.visible = 'yes';
			p.sections.details.list.payment_status_text.visible = 'no';
			p.sections.details.list.shipping_status_text.visible = 'no';
			p.sections.details.list.manufacturing_status_text.visible = 'no';
		}
		if( rsp.invoice.customer_id > 0 ) {
			p.sections.customer_details.addTxt = 'Edit Customer';
			p.sections.customer_details.changeTxt = 'Change Customer';
		} else {
			p.sections.customer_details.addTxt = 'Add Customer';
			p.sections.customer_details.changeTxt = '';
		}
		// Must be owner/employee/sysadmin to add/edit customers
		p.rightbuttons = {};
		if( M.curBusiness.permissions.owners == null 
			&& M.curBusiness.permissions.employees == null 
			&& M.curBusiness.permissions.salesreps != null
			&& (M.userPerms&0x01) == 0
			) {
			p.sections.customer_details.addTxt = '';
			p.sections.customer_details.changeTxt = '';
			p.sections._buttons.buttons.delete.visible=(rsp.invoice.status==10?'yes':'no');
			p.sections.shipitems.addTxt = (rsp.invoice.status == 10)?'Add':'';
			p.sections.items.addTxt = (rsp.invoice.status == 10)?'Add':'';
			if( ((M.curBusiness.sapos.settings['rules-salesreps-invoice-po_number'] != null
					&& M.curBusiness.sapos.settings['rules-salesreps-invoice-po_number'] == 'edit') 
				|| (M.curBusiness.sapos.settings['rules-salesreps-invoice-billing'] != null
					&& M.curBusiness.sapos.settings['rules-salesreps-billing'] == 'edit') 
				|| (M.curBusiness.sapos.settings['rules-salesreps-invoice-shipping'] != null
					&& M.curBusiness.sapos.settings['rules-salesreps-invoice-shipping'] == 'edit') 
				|| (M.curBusiness.sapos.settings['rules-salesreps-invoice-work'] != null
					&& M.curBusiness.sapos.settings['rules-salesreps-invoice-work'] == 'edit') 
				) && rsp.invoice.status < 20 ) {
				p.addButton('edit', 'Edit', 'M.ciniki_sapos_invoice.editInvoice(\'M.ciniki_sapos_invoice.showInvoice();\',M.ciniki_sapos_invoice.invoice.invoice_id);');
			}
		} else {
			if( rsp.invoice.status < 50 || M.curBusiness.permissions.owners != null ) {
				p.addButton('edit', 'Edit', 'M.ciniki_sapos_invoice.editInvoice(\'M.ciniki_sapos_invoice.showInvoice();\',M.ciniki_sapos_invoice.invoice.invoice_id);');
			}
			if( rsp.invoice.invoice_type == '10' ) {
				if( p.rightbuttons['add'] == null ) {
					this.invoice.addButton('add', 'Invoice', 'M.ciniki_sapos_invoice.createInvoice(M.ciniki_sapos_invoice.invoice.cb,0,null);');
				}
			} else if( p.rightbuttons['add'] != null ) {
				delete(p.rightbuttons['add']);
			}
			p.sections._buttons.buttons.delete.visible=(rsp.invoice.status<40&&(rsp.invoice.transactions==null||rsp.invoice.transactions.length==0)&&(rsp.invoice.shipments==null||rsp.invoice.shipments.length==0))?'yes':'no';
			p.sections.shipitems.addTxt = (rsp.invoice.status < 50)?'Add':'';
			p.sections.items.addTxt = (rsp.invoice.status < 50)?'Add':'';
		}
		if( rsp.invoice.billing_name != '' || rsp.invoice.billing_address1 != '' ) {
			p.sections.billing.visible = 'yes';
			p.data.billing_address = M.formatAddress({
				'name':p.data.billing_name,
				'address1':p.data.billing_address1,
				'address2':p.data.billing_address2,
				'city':p.data.billing_city,
				'province':p.data.billing_province,
				'postal':p.data.billing_postal,
				'country':p.data.billing_country,
				});
			p.data.shipping_address = M.formatAddress({
				'name':p.data.shipping_name,
				'address1':p.data.shipping_address1,
				'address2':p.data.shipping_address2,
				'city':p.data.shipping_city,
				'province':p.data.shipping_province,
				'postal':p.data.shipping_postal,
				'country':p.data.shipping_country,
				'phone':p.data.shipping_phone,
				});
		} else {
			p.sections.billing.visible = 'no';
		}
		if( (M.curBusiness.modules['ciniki.sapos'].flags&0x020000)>0 && rsp.invoice.work_address1 != '' ) {
			p.sections.work.visible = 'yes';
			p.data.work_address = M.formatAddress({
				'address1':p.data.work_address1,
				'address2':p.data.work_address2,
				'city':p.data.work_city,
				'province':p.data.work_province,
				'postal':p.data.work_postal,
				'country':p.data.work_country,
				});
		} else {
			p.sections.work.visible = 'no';
		}
//		p.sections.shipping.visible=(rsp.invoice.shipping_status>0&&(rsp.invoice.shipping_name!=''||rsp.invoice.shipping_address1!=''))?'yes':'no';
		p.sections.shipping.visible=(rsp.invoice.shipping_status>0||rsp.invoice.invoice_type==40)?'yes':'no';
		p.sections.tallies.visible='yes';
		p.data.tallies = {};
		p.data.tallies['subtotal'] = {'tally':{'description':'Sub Total', 'amount':(rsp.invoice.subtotal_amount!=null)?rsp.invoice.subtotal_amount_display:'0.00'}};
		if( rsp.invoice.discount_amount > 0 ) {
			var discount = '';
			if( rsp.invoice.subtotal_discount_amount != 0 ) {
				discount = '-' + rsp.invoice.subtotal_discount_amount_display;
			}
			if( rsp.invoice.subtotal_discount_percentage != 0 ) {
				discount += ((rsp.invoice.subtotal_discount_amount != 0)?', ':'') + '-' + rsp.invoice.subtotal_discount_percentage + '%';
			}
			p.data.tallies['discount_amount'] = {'tally':{'description':'Overall discount (' + discount + ')', 'amount':(rsp.invoice.discount_amount!=null)?'-'+rsp.invoice.discount_amount_display:'0.00'}};
		}
		if( (rsp.invoice.flags&0x03) > 1 ) {
			p.data.tallies['shipping'] = {'tally':{'description':'Shipping & Handling', 'amount':(rsp.invoice.shipping_amount!=null)?rsp.invoice.shipping_amount_display:'0.00'}};
		}
		if( rsp.invoice.taxes != null ) {
			for(i in rsp.invoice.taxes) {
				p.data.tallies['tax'+i] = {'tally':{'description':rsp.invoice.taxes[i].tax.description,
					'amount':rsp.invoice.taxes[i].tax.amount_display}};
			}
		}
		p.data.tallies['total'] = {'tally':{'description':'Total', 'amount':rsp.invoice.total_amount_display}};
		if( rsp.invoice.total_savings > 0 && (rsp.invoice.flags&0x01) == 0 ) {
			p.data.tallies['savings'] = {'tally':{'description':'Savings', 'amount':rsp.invoice.total_savings_display}};
		}
		if( rsp.invoice.transactions.length > 0 ) {
			p.sections.transactions.visible='yes';
			p.data.transactions = rsp.invoice.transactions;
			if( rsp.invoice.balance_amount_display != null ) {
				p.data.transactions.push({'transaction':{'id':'0', 
					'transaction_date':'Balance Owing', 
					'transaction_type':0,
					'transaction_type_text':'',
					'customer_amount':rsp.invoice.balance_amount_display}});
			}
		} else {
			p.sections.transactions.visible='no';
		}
		if( M.curBusiness.sapos != null && M.curBusiness.sapos.settings != null 
			&& M.curBusiness.sapos.settings['paypal-api-processing'] != null 
			&& M.curBusiness.sapos.settings['paypal-api-processing'] == 'yes' ) {
			p.sections._buttons.buttons.terminal.visible='yes';
		} else {
			p.sections._buttons.buttons.terminal.visible='no';
		}
		if( rsp.invoice.shipping_status > 0 ) {
			if( M.curBusiness.permissions.owners != null
			 	|| M.curBusiness.permissions.employees != null
				|| (M.userPerms&0x01) > 0 ) {
//				p.sections._buttons.buttons.picklist.visible = (rsp.invoice.status>15&&rsp.invoice.status<50?'yes':'no');
				p.sections.shipments.addTxt = (rsp.invoice.status<50?'Add Shipment':'');
			} else {
				p.sections.shipments.addTxt = '';
//				p.sections._buttons.buttons.picklist.visible = 'no';
			}
			p.sections.shipments.visible = (rsp.invoice.status>15?'yes':'no');
			if( p.data.shipments != null ) {
				p.sections.shipments.visible = 'yes';
				var max_num = 0;
				for(i in p.data.shipments) {
					if( p.data.shipments[i].shipment.shipment_number != null && parseInt(p.data.shipments[i].shipment.shipment_number) > max_num ) {
						max_num = parseInt(p.data.shipments[i].shipment.shipment_number);
					}
				}
				p.nextShipmentNumber = max_num+1;
			} else {
//				p.sections.shipments.visible = 'no';
				p.nextShipmentNumber = 1;
			}
		} else {
//			p.sections._buttons.buttons.picklist.visible = 'no';
			if( p.data.shipments != null && p.data.shipments.length > 0 ) {
				p.sections.shipments.addTxt = '';
				p.sections.shipments.visible = 'yes';
			} else {
				p.sections.shipments.visible = 'no';
			}
		}
		p.sections._buttons.buttons.submitorder.visible = ((rsp.invoice.invoice_type=='40'||rsp.invoice.invoice_type=='20')&&rsp.invoice.items.length>0&&rsp.invoice.status==10?'yes':'no');
//		p.sections._buttons.buttons.print.visible = (rsp.invoice.status>10?'yes':'no');
//		p.sections._buttons.buttons.printenv.visible = (rsp.invoice.status>10?'yes':'no');
		if( rsp.invoice.status > 10 && (M.curBusiness.sapos.settings['ui-options-print-picklist'] == null 
			|| M.curBusiness.sapos.settings['ui-options-print-picklist'] == 'yes') 
			&& ( M.curBusiness.permissions.owners != null
			 	|| M.curBusiness.permissions.employees != null
				|| (M.userPerms&0x01) > 0 ) 
			) {
			p.sections._buttons.buttons.picklist.visible = 'yes';
		} else {
			p.sections._buttons.buttons.picklist.visible = 'no';
		}
		if( rsp.invoice.status > 10 && (M.curBusiness.sapos.settings['ui-options-print-invoice'] == null 
			|| M.curBusiness.sapos.settings['ui-options-print-invoice'] == 'yes') 
			) {
			p.sections._buttons.buttons.print.visible = 'yes';
		} else {
			p.sections._buttons.buttons.print.visible = 'no';
		}
		if( rsp.invoice.status > 10 && (M.curBusiness.sapos.settings['ui-options-print-envelope'] == null 
			|| M.curBusiness.sapos.settings['ui-options-print-envelope'] == 'yes') 
			) {
			p.sections._buttons.buttons.printenv.visible = 'yes';
		} else {
			p.sections._buttons.buttons.printenv.visible = 'no';
		}
		if( rsp.invoice.invoice_type == 90 ) {
			p.sections._buttons.buttons.record.visible = 'no';
			p.sections._buttons.buttons.terminal.visible = 'no';
			p.sections._buttons.buttons.submitorder.visible = 'no';
			p.sections._buttons.buttons.print.visible = 'no';
			p.sections._buttons.buttons.picklist.visible = 'no';
			p.sections._buttons.buttons.printquote.visible = 'yes';
			p.sections._buttons.buttons.delete.visible = 'yes';
			p.sections._buttons.buttons.printenv.visible = 'no';
		} else {
			p.sections._buttons.buttons.printquote.visible = 'no';
		}
		if( rsp.invoice.customer_id > 0 && rsp.invoice.invoice_type != 11 && rsp.invoice.invoice_type != 12 
			&& M.curBusiness.modules['ciniki.mail'] != null
			) {
			p.sections._buttons.buttons.email.visible = 'yes';
		} else {
			p.sections._buttons.buttons.email.visible = 'no';
		}
//		p.sections.customer_notes.visible=(rsp.invoice.invoice_notes!='')?'yes':'no';
//		p.sections.internal_notes.visible=(rsp.invoice.internal_notes!='')?'yes':'no';
		this.invoice.addButton('next', 'Next');
		p.refresh();
		p.show(cb);
	};

	this.updateInvoiceCustomer = function(cid) {
		// If the customer has changed, then update the details of the invoice
		if( cid != null && this.invoice.data.customer_id != cid ) {
			// Update the customer attached to the invoice, and update shipping/billing records for the invoice
			M.api.getJSONCb('ciniki.sapos.invoiceUpdate', {'business_id':M.curBusinessID,
				'invoice_id':this.invoice.invoice_id, 'customer_id':cid, 
				'billing_update':'yes', 'shipping_update':'yes', 'work_update':'yes'}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_sapos_invoice.showInvoiceFinish(null,rsp);
				});
		} else {
			M.api.getJSONCb('ciniki.sapos.invoiceUpdate', {'business_id':M.curBusinessID,
				'invoice_id':this.invoice.invoice_id, 
				'billing_update':'yes', 'shipping_update':'yes'}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_sapos_invoice.showInvoiceFinish(null,rsp);
				});
//			this.showInvoice();
		}
	};

	this.editInvoice = function(cb, iid) {
		if( iid != null ) { this.edit.invoice_id = iid; }
		if( this.invoice.invoice_id > 0 ) {
			M.api.getJSONCb('ciniki.sapos.invoiceGet', {'business_id':M.curBusinessID,
				'invoice_id':this.invoice.invoice_id, 'salesreps':'yes'}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					var p = M.ciniki_sapos_invoice.edit;
					p.data = rsp.invoice;
					// Sales Reps
					if( (M.curBusiness.modules['ciniki.sapos'].flags&0x0800) > 0 
						&& (M.curBusiness.permissions.owners != null
							|| M.curBusiness.permissions.employees != null
							|| (M.userPerms&0x01) > 0
							)
						) {
						if( rsp.salesreps != null ) {
							p.sections.details.fields.salesrep_id.active = 'yes';
							var reps = {'0':'None'};
							for(i in rsp.salesreps) {
								reps[rsp.salesreps[i].user.id] = rsp.salesreps[i].user.name;
							}
							p.sections.details.fields.salesrep_id.options = reps;
						} else {
							p.sections.details.fields.salesrep_id.options = {'0':'None'};
						}
					} else {
						p.sections.details.fields.salesrep_id.active = 'no';
					}
					if( p.sections.details.fields.payment_status.visible == 'yes' ) {
						p.sections.details.fields.payment_status.visible = ((M.curBusiness.modules['ciniki.sapos'].flags&0x0200)>0||rsp.invoice.payment_status>0)?'yes':'no';
					}
					if( p.sections.details.fields.shipping_status.visible == 'yes' ) {
						p.sections.details.fields.shipping_status.visible = ((M.curBusiness.modules['ciniki.sapos'].flags&0x40)>0||rsp.invoice.shipping_status>0)?'yes':'no';
					}
					if( p.sections.details.fields.manufacturing_status.visible == 'yes' ) {
						p.sections.details.fields.manufacturing_status.visible = ((M.curBusiness.modules['ciniki.sapos'].flags&0x80)>0||rsp.invoice.manufacturing_status>0)?'yes':'no';
					}
					p.refresh();
					p.show(cb);
					p.updateForm();
				});
		}
	};

	this.saveInvoice = function() {
		var c = this.edit.serializeForm('no');
		if( c != '' ) {
			M.api.postJSONCb('ciniki.sapos.invoiceUpdate', {'business_id':M.curBusinessID,
				'invoice_id':this.edit.invoice_id}, c, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_sapos_invoice.edit.close();
				});
		} else {
			this.edit.close();
		}
	};

	this.updateInvoiceField = function(f, n) {
		var v = prompt(n, M.ciniki_sapos_invoice.invoice.data[f]);
		if( v != null && v != '' ) {
			c = '&' + encodeURIComponent(f) + '=' + encodeURIComponent(v);
			M.api.postJSONCb('ciniki.sapos.invoiceUpdate', {'business_id':M.curBusinessID,
				'invoice_id':this.invoice.invoice_id}, c, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_sapos_invoice.showInvoice();
				});
		}
	};

	this.deleteInvoice = function(iid) {
		if( iid <= 0 ) { return false; }
		if( confirm("Are you sure you want to remove this invoice from the system?") ) {
			M.api.getJSONCb('ciniki.sapos.invoiceDelete', {'business_id':M.curBusinessID,
				'invoice_id':iid}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_sapos_invoice.invoice.close();
				});
		}
	};

	this.printInvoice = function(iid) {
		if( iid <= 0 ) { return false; }
		window.open(M.api.getUploadURL('ciniki.sapos.invoicePDF',
			{'business_id':M.curBusinessID, 'invoice_id':iid}));
	};

	this.emailCustomer = function(cb, invoice) {
		this.email.invoice_id = invoice.id;
		this.email.data.subject = 'Invoice #' + invoice.invoice_number;
		if( M.curBusiness.sapos.settings['invoice-email-message'] != null ) {
			this.email.data.textmsg = M.curBusiness.sapos.settings['invoice-email-message'];
		} else {
			this.email.data.textmsg = 'Please find your invoice attached.';
		}
		if( invoice.invoice_type == 20 ) {
			this.email.data.subject = 'Shopping Cart #' + invoice.invoice_number;
			if( M.curBusiness.sapos.settings['cart-email-message'] != null ) {
				this.email.data.textmsg = M.curBusiness.sapos.settings['cart-email-message'];
			} 
		} else if( invoice.invoice_type == 30 ) {
			this.email.data.subject = 'Receipt #' + invoice.invoice_number;
			if( M.curBusiness.sapos.settings['pos-email-message'] != null ) {
				this.email.data.textmsg = M.curBusiness.sapos.settings['pos-email-message'];
			} 
		} else if( invoice.invoice_type == 40 ) {
			this.email.data.subject = 'Order #' + invoice.invoice_number;
			if( M.curBusiness.sapos.settings['order-email-message'] != null ) {
				this.email.data.textmsg = M.curBusiness.sapos.settings['order-email-message'];
			} 
		} else if( invoice.invoice_type == 90 ) {
			this.email.data.subject = 'Quote #' + invoice.invoice_number;
			if( M.curBusiness.sapos.settings['quote-email-message'] != null ) {
				this.email.data.textmsg = M.curBusiness.sapos.settings['quote-email-message'];
			} 
		}
		this.email.refresh();
		this.email.show(cb);
	};

	this.sendEmail = function() {
		var subject = this.email.formFieldValue(this.email.sections._subject.fields.subject, 'subject');
		var textmsg = this.email.formFieldValue(this.email.sections._textmsg.fields.textmsg, 'textmsg');
		M.api.getJSONCb('ciniki.sapos.invoicePDF', {'business_id':M.curBusinessID, 
			'invoice_id':this.email.invoice_id, 'subject':subject, 'textmsg':textmsg, 'output':'pdf', 'email':'yes'}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				M.ciniki_sapos_invoice.email.close();
			});
	};

	this.printQuote = function(iid) {
		if( iid <= 0 ) { return false; }
		window.open(M.api.getUploadURL('ciniki.sapos.quotePDF',
			{'business_id':M.curBusinessID, 'invoice_id':iid}));
	};

	this.printPickList = function(iid) {
		if( iid <= 0 ) { return false; }
		window.open(M.api.getUploadURL('ciniki.sapos.invoicePDF',
			{'business_id':M.curBusinessID, 'invoice_id':iid, 'type':'picklist'}));
	};

	this.printEnvelope = function(iid) {
		if( iid <= 0 ) { return false; }
		window.open(M.api.getUploadURL('ciniki.sapos.invoicePDFEnv',
			{'business_id':M.curBusinessID, 'invoice_id':iid}));
	};

	this.submitOrder = function(iid) {
		M.api.getJSONCb('ciniki.sapos.invoiceAction', {'business_id':M.curBusinessID,
			'invoice_id':iid, 'action':'submit'}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				M.ciniki_sapos_invoice.invoice.close();
			});
	};

	this.terminalDetails = function() {
		var p = M.ciniki_sapos_invoice.invoice;
		var d = {
			'invoice_id':p.invoice_id,
			'currency':'CAD',
			'total':p.data.balance_amount_display,
			'first_name':'',
			'last_name':'',
			'line1':p.data.billing_address1,
			'line2':p.data.billing_address2,
			'city':p.data.billing_city,
			'province':p.data.billing_province,
			'postal':p.data.billing_postal,
			'country':p.data.billing_country,
			'phone':'',
		};
		if( M.curBusiness.intl != null && M.curBusiness.intl['intl-default-currency'] != null ) {
			d.currency = M.curBusiness.intl['intl-default-currency'];
		}
		if( p.data.billing_name != '' ) {
			var sn = p.data.billing_name.split(' ');
			for(i in sn) {
				if( i == 0 || d.first_name == '' ) { d.first_name = sn[i]; }
				else if( i > 0 && sn[i].length == 1 ) { d.first_name += (d.first_name!=''?' ':'') + sn[i]; }
				else if( i == (sn.length-2) && sn[i].length > 1 && sn[i].length < 4 ) { d.last_name += sn[i] + ' '; }
				else if( i > 0 && i == (sn.length-1) ) { d.last_name += sn[i]; }
			}
		}
		if( p.data.customer.phone_home != null && p.data.customer.phone_home != '' ) {
			d.phone = p.data.customer.phone_home.value;
		}
		if( p.data.customer.phone_cell != null && p.data.customer.phone_cell != '' ) {
			d.phone = p.data.customer.phone_cell.value;
		}
		return d;
	};

	this.editItem = function(cb, iid, inid) {
		if( iid != null ) { this.item.item_id = iid; }
		if( inid != null ) { this.item.invoice_id = inid; }
//		if( ppid != null ) { this.item.pricepoint_id = ppid; }
		if( this.item.item_id > 0 ) {
			this.item.sections._buttons.buttons.delete.visible = 'yes';
			M.api.getJSONCb('ciniki.sapos.invoiceItemGet', {'business_id':M.curBusinessID,
				'item_id':this.item.item_id, 'taxtypes':'yes'}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					var p = M.ciniki_sapos_invoice.item;
					p.data = rsp.item;
					p.price_id = rsp.item.price_id;
					p.object = rsp.item.object;
					p.object_id = rsp.item.object_id;
					if( (rsp.item.flags&0x40) > 0 ) {
						p.sections.details.fields.force_backorder.active = 'yes';
						p.data.force_backorder = ((rsp.item.flags&0x0200)>0?'yes':'no');
					} else {
						p.sections.details.fields.force_backorder.active = 'no';
					}
//					if( rsp.taxtypes != null ) {
//						p.sections.details.fields.taxtype_id.active = 'yes';
//						p.sections.details.fields.taxtype_id.type=((rsp.taxtypes.length>4)?'select':'toggle');
//						p.sections.details.fields.taxtype_id.options = {'0':'No Tax'};
//						for(i in rsp.taxtypes) {
//							p.sections.details.fields.taxtype_id.options[rsp.taxtypes[i].type.id] = rsp.taxtypes[i].type.name + ((rsp.taxtypes[i].type.rates==''||rsp.taxtypes[i].type.rates==null)?', No Taxes':', ' + rsp.taxtypes[i].type.rates);
//						}
//						p.sections.details.fields.taxtype_id.toggles=p.sections.details.fields.taxtype_id.options;
//					} else {
//						p.sections.details.fields.taxtype_id.active = 'no';
//					}
					p.refresh();
					p.show(cb);
				});
		} else {
			var p = M.ciniki_sapos_invoice.item;
			p.reset();
			p.object = '';
			p.object_id = 0;
			p.price_id = 0;
			p.sections._buttons.buttons.delete.visible = 'no';
			p.sections.details.fields.force_backorder.active = 'no';
//			if( M.curBusiness.modules['ciniki.taxes'] != null ) {
//				M.api.getJSONCb('ciniki.taxes.typeList', {'business_id':M.curBusinessID}, function(rsp) {
//					if( rsp.stat != 'ok' ) {
//						M.api.err(rsp);
//						return false;
//					}
//					p.sections.details.fields.taxtype_id.active = 'yes';
//					p.sections.details.fields.taxtype_id.options = {'0':'No Tax'};
//					for(i in rsp.active) {
//						p.sections.details.fields.taxtype_id.options[rsp.active[i].type.id] = rsp.active[i].type.name + ((rsp.active[i].type.rates==''||rsp.active[i].type.rates==null)?', No Taxes':', ' + rsp.active[i].type.rates);
//					}
//					p.refresh();
//					p.show(cb);
//				});
//			} else {
				p.data = {'force_backorder':'no'};
//				p.sections.details.fields.taxtype_id.active = 'no';
				p.refresh();
				p.show(cb);
//			}
		}
	};

	this.saveItem = function() {
		if( this.item.item_id > 0 ) {
			var c = this.item.serializeForm('no');
			if( this.item.object != this.item.data.object ) {
				c += 'object=' + this.item.object + '&';
			}
			if( this.item.object_id != this.item.data.object_id ) {
				c += 'object_id=' + this.item.object_id + '&';
			}
			if( this.item.price_id != this.item.data.price_id ) {
				c += 'price_id=' + this.item.price_id + '&';
			}
			if( this.item.sections.details.fields.force_backorder.active == 'yes' ) {
				var fb = this.item.formFieldValue(this.item.sections.details.fields.force_backorder, 'force_backorder');
				if( fb != this.item.data.force_backorder ) {
					if( fb == 'yes' ) {
						c += 'flags=' + (this.item.data.flags|0x0200) + '&';
					} else {
						c += 'flags=' + (this.item.data.flags^0x0200) + '&';
					}
				}
			}
			if( c != '' ) {
				M.api.postJSONCb('ciniki.sapos.invoiceItemUpdate', {'business_id':M.curBusinessID,
					'item_id':this.item.item_id}, c, function(rsp) {
						if( rsp.stat != 'ok' ) {
							M.api.err(rsp);
							return false;
						}
						M.ciniki_sapos_invoice.item.close();
					});
			} else {
				this.item.close();
			}
		} else {
			var c = this.item.serializeForm('yes');
			if( this.item.object != '' ) {
				c += 'object=' + this.item.object + '&';
			}
			if( this.item.object_id > 0 ) {
				c += 'object_id=' + this.item.object_id + '&';
			}
			if( this.item.price_id > 0 ) {
				c += 'price_id=' + this.item.price_id + '&';
			}
			M.api.postJSONCb('ciniki.sapos.invoiceItemAdd', {'business_id':M.curBusinessID,
				'invoice_id':this.item.invoice_id}, c, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_sapos_invoice.item.close();
				});
		}
	};

	this.deleteItem = function(iid) {
		if( iid <= 0 ) { return false; }
		if( confirm("Are you sure you want to remove this item?") ) {
			M.api.getJSONCb('ciniki.sapos.invoiceItemDelete', {'business_id':M.curBusinessID,
				'item_id':iid}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_sapos_invoice.item.close();
				});
		}
	};

	this.editTransaction = function(cb, tid, inid, date, amount) {
		if( tid != null ) { this.transaction.transaction_id = tid; }
		if( inid != null ) { this.transaction.invoice_id = inid; }
		if( this.transaction.transaction_id > 0 ) {
			M.api.getJSONCb('ciniki.sapos.transactionGet', {'business_id':M.curBusinessID,
				'transaction_id':this.transaction.transaction_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					var p = M.ciniki_sapos_invoice.transaction;
					p.data = rsp.transaction;
					p.sections._buttons.buttons.delete.visible='yes';
					p.refresh();
					p.show(cb);
				});
		} else {
			var p = M.ciniki_sapos_invoice.transaction;
			p.reset();
			p.data = {};
			if( date != null && date != '' ) {
				if( date == 'now' ) {
					var dt = new Date();
					p.data.transaction_date = M.dateFormat(dt) + ' ' + M.dateMake12hourTime2(dt);
				} else {
					p.data.transaction_date = date;
				}
			}
			if( amount != null && amount != '' ) { p.data.customer_amount = amount;}
			p.sections._buttons.buttons.delete.visible='no';
			p.refresh();
			p.show(cb);
		}
	};

	this.saveTransaction = function() {
		if( this.transaction.transaction_id > 0 ) {
			var c = this.transaction.serializeForm('no');
			if( c != '' ) {
				M.api.postJSONCb('ciniki.sapos.transactionUpdate', {'business_id':M.curBusinessID,
					'transaction_id':this.transaction.transaction_id}, c, function(rsp) {
						if( rsp.stat != 'ok' ) {
							M.api.err(rsp);
							return false;
						}
						M.ciniki_sapos_invoice.transaction.close();
					});
			} else {
				this.transaction.close();
			}
		} else {
			var c = this.transaction.serializeForm('yes');
			M.api.postJSONCb('ciniki.sapos.transactionAdd', {'business_id':M.curBusinessID,
				'invoice_id':this.transaction.invoice_id}, c, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_sapos_invoice.transaction.close();
				});
		}
	};

	this.deleteTransaction = function(tid) {
		if( tid <= 0 ) { return false; }
		if( confirm("Are you sure you want to remove this transaction?") ) {
			M.api.getJSONCb('ciniki.sapos.transactionDelete', {'business_id':M.curBusinessID,
				'transaction_id':tid}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_sapos_invoice.transaction.close();
				});
		}
	};
}
