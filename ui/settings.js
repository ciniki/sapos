//
function ciniki_sapos_settings() {
	this.toggleOptions = {'no':'Hide', 'yes':'Display'};
	this.positionOptions = {'left':'Left', 'center':'Center', 'right':'Right', 'off':'Off'};
	this.weightUnits = {
		'10':'lb',
		'20':'kg',
		};

	this.init = function() {
		//
		// The menu panel
		//
		this.menu = new M.panel('Settings',
			'ciniki_sapos_settings', 'menu',
			'mc', 'narrow', 'sectioned', 'ciniki.sapos.settings.menu');
		this.menu.sections = {
			'invoice':{'label':'Invoices', 'list':{
				'invoice':{'label':'Invoices', 'fn':'M.ciniki_sapos_settings.editInvoice(\'M.ciniki_sapos_settings.showMenu();\');'},
				'qi':{'label':'Quick Invoices', 'visible':'no', 'fn':'M.ciniki_sapos_settings.showQI(\'M.ciniki_sapos_settings.showMenu();\');'},
				}},
			'shipments':{'label':'Shipments', 'visible':'no', 'list':{
				'shipments':{'label':'Settings', 'fn':'M.ciniki_sapos_settings.editShipment(\'M.ciniki_sapos_settings.showMenu();\');'},
				}},
			'expenses':{'label':'Expenses', 'visible':'no', 'list':{
				'expenses':{'label':'Expense Categories', 'fn':'M.ciniki_sapos_settings.showExpenseCategories(\'M.ciniki_sapos_settings.showMenu();\');'},
				}},
			'mileage':{'label':'Mileage', 'visible':'no', 'list':{
				'mileagerates':{'label':'Rates', 'fn':'M.ciniki_sapos_settings.showMileageRates(\'M.ciniki_sapos_settings.showMenu();\');'},
				}},
			'paypal':{'label':'Paypal', 'list':{
				'paypal':{'label':'Paypal', 'fn':'M.ciniki_sapos_settings.editPaypal(\'M.ciniki_sapos_settings.showMenu();\');'},
				}},
		};
		this.menu.addClose('Back');

		//
		// The invoice settings panel
		//
		this.invoice = new M.panel('Invoice Settings',
			'ciniki_sapos_settings', 'invoice',
			'mc', 'medium', 'sectioned', 'ciniki.sapos.settings.invoice');
		this.invoice.sections = {
			'image':{'label':'Header Image', 'fields':{
				'invoice-header-image':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no'},
				}},
			'header':{'label':'Header Address Options', 'fields':{
				'invoice-header-contact-position':{'label':'Position', 'type':'toggle', 'default':'center', 'toggles':this.positionOptions},
				'invoice-header-business-name':{'label':'Business Name', 'type':'toggle', 'default':'yes', 'toggles':this.toggleOptions},
				'invoice-header-business-address':{'label':'Address', 'type':'toggle', 'default':'yes', 'toggles':this.toggleOptions},
				'invoice-header-business-phone':{'label':'Phone', 'type':'toggle', 'default':'yes', 'toggles':this.toggleOptions},
				'invoice-header-business-cell':{'label':'Cell', 'type':'toggle', 'default':'yes', 'toggles':this.toggleOptions},
				'invoice-header-business-fax':{'label':'Fax', 'type':'toggle', 'default':'yes', 'toggles':this.toggleOptions},
				'invoice-header-business-email':{'label':'Email', 'type':'toggle', 'default':'yes', 'toggles':this.toggleOptions},
				'invoice-header-business-website':{'label':'Website', 'type':'toggle', 'default':'yes', 'toggles':this.toggleOptions},
				}},
			'_bottom_msg':{'label':'Invoice Message', 'fields':{
				'invoice-bottom-message':{'label':'', 'hidelabel':'yes', 'type':'textarea'},
				}},
			'_footer_msg':{'label':'Footer Message', 'fields':{
				'invoice-footer-message':{'label':'', 'hidelabel':'yes', 'type':'text'},
				}},
			'_buttons':{'label':'', 'buttons':{
				'save':{'label':'Save', 'fn':'M.ciniki_sapos_settings.saveInvoice();'},
				}},
		};
		this.invoice.fieldHistoryArgs = function(s, i) {
			return {'method':'ciniki.sapos.settingsHistory', 
				'args':{'business_id':M.curBusinessID, 'setting':i}};
		}
		this.invoice.fieldValue = function(s, i, d) {
			if( this.data[i] == null && d.default != null ) { return d.default; }
			return this.data[i];
		};
		this.invoice.addDropImage = function(iid) {
			M.ciniki_sapos_settings.invoice.setFieldValue('invoice-header-image', iid);
			return true;
		};
		this.invoice.deleteImage = function(fid) {
			this.setFieldValue(fid, 0);
			return true;
		};
		this.invoice.addButton('save', 'Save', 'M.ciniki_sapos_settings.saveInvoice();');
		this.invoice.addClose('Cancel');

		//
		// The invoice settings panel
		//
		this.shipment = new M.panel('Shipment Settings',
			'ciniki_sapos_settings', 'shipment',
			'mc', 'medium', 'sectioned', 'ciniki.sapos.settings.shipment');
		this.shipment.sections = {
			'_defaults':{'label':'Defaults', 'fields':{
				'shipments-default-shipper':{'label':'Shipper', 'type':'text'},
				'shipments-default-weight-units':{'label':'Units', 'type':'toggle', 'default':'10', 'toggles':this.weightUnits},
				}},
			'_buttons':{'label':'', 'buttons':{
				'save':{'label':'Save', 'fn':'M.ciniki_sapos_settings.saveShipment();'},
				}},
		};
		this.shipment.fieldHistoryArgs = function(s, i) {
			return {'method':'ciniki.sapos.settingsHistory', 
				'args':{'business_id':M.curBusinessID, 'setting':i}};
		}
		this.shipment.fieldValue = function(s, i, d) {
			if( this.data[i] == null && d.default != null ) { return d.default; }
			return this.data[i];
		};
		this.shipment.addButton('save', 'Save', 'M.ciniki_sapos_settings.saveShipment();');
		this.shipment.addClose('Cancel');

		//
		// The qi settings panel
		//
		this.qi = new M.panel('Quick Invoice',
			'ciniki_sapos_settings', 'qi',
			'mc', 'medium', 'sectioned', 'ciniki.sapos.settings.qi');
		this.qi.sections = {
			'items':{'label':'Items', 'type':'simplegrid', 'num_cols':2,
				'addTxt':'Add',
				'addFn':'M.ciniki_sapos_settings.editQIItem(\'M.ciniki_sapos_settings.showQI();\',0);',
				}
		};
		this.qi.sectionData = function(s) { return this.data[s]; }
		this.qi.cellValue = function(s, i, j, d) {
			switch(j) {
				case 0: return d.item.name;
				case 1: return d.item.unit_amount;
			}
		};
		this.qi.rowFn = function(s, i, d) {
			return 'M.ciniki_sapos_settings.editQIItem(\'M.ciniki_sapos_settings.showQI();\',\'' + d.item.id + '\');';
		};
		this.qi.addButton('add', 'Add', 'M.ciniki_sapos_settings.editQIItem(\'M.ciniki_sapos_settings.showQI();\',0);');
		this.qi.addClose('Back');

		//
		// The qi item edit panel
		//
		this.qiedit = new M.panel('Expense Category',
			'ciniki_sapos_settings', 'qiedit',
			'mc', 'medium', 'sectioned', 'ciniki.sapos.settings.qiedit');
		this.qiedit.item_id = 0;
		this.qiedit.data = {};
		this.qiedit.sections = {
			'item':{'label':'Item', 'fields':{
				'name':{'label':'Name', 'type':'text'},
				'description':{'label':'Description', 'type':'text'},
				'quantity':{'label':'Quantity', 'type':'text', 'size':'small'},
				'unit_amount':{'label':'Unit Amount', 'type':'text', 'size':'small'},
				'unit_discount_amount':{'label':'Discount Amount', 'type':'text', 'size':'small'},
				'unit_discount_percentage':{'label':'Discount Percentage', 'type':'text', 'size':'small'},
				'taxtype_id':{'label':'Taxes', 'type':'select', 'options':{}},
				}},
			'_buttons':{'label':'', 'buttons':{
				'save':{'label':'Save', 'fn':'M.ciniki_sapos_settings.saveQIItem();'},
				'delete':{'label':'Delete', 'fn':'M.ciniki_sapos_settings.deleteQIItem(M.ciniki_sapos_settings.qiedit.item_id);'},
				}},
		};
		this.qiedit.fieldValue = function(s, i, d) {
			if( this.data[i] == null ) { return ''; }
			return this.data[i];
		};
		this.qiedit.fieldHistoryArgs = function(s, i) {
			return {'method':'ciniki.sapos.history', 'args':{'business_id':M.curBusinessID,
				'object':'ciniki.sapos.qi_item', 'object_id':this.item_id, 'field':i}};
		};
		this.qiedit.addClose('Cancel');

		//
		// The expenses settings panel
		//
		this.ecats = new M.panel('Expense Categories',
			'ciniki_sapos_settings', 'ecats',
			'mc', 'medium', 'sectioned', 'ciniki.sapos.settings.ecats');
		this.ecats.sections = {
			'categories':{'label':'Categories', 'type':'simplegrid', 'num_cols':1,
				'addTxt':'Add',
				'addFn':'M.ciniki_sapos_settings.editExpenseCategory(\'M.ciniki_sapos_settings.showExpenseCategories();\',0);',
				}
		};
		this.ecats.sectionData = function(s) { return this.data[s]; }
		this.ecats.cellValue = function(s, i, j, d) {
			switch(j) {
				case 0: return d.category.name;
			}
		};
		this.ecats.rowFn = function(s, i, d) {
			return 'M.ciniki_sapos_settings.editExpenseCategory(\'M.ciniki_sapos_settings.showExpenseCategories();\',\'' + d.category.id + '\');';
		};
		this.ecats.addButton('add', 'Add', 'M.ciniki_sapos_settings.editExpenseCategory(\'M.ciniki_sapos_settings.showExpenseCategories();\',0);');
		this.ecats.addClose('Back');

		//
		// The expense category edit panel
		//
		this.ecatedit = new M.panel('Expense Category',
			'ciniki_sapos_settings', 'ecatedit',
			'mc', 'medium', 'sectioned', 'ciniki.sapos.settings.ecatedit');
		this.ecatedit.category_id = 0;
		this.ecatedit.data = {};
		this.ecatedit.sections = {
			'category':{'label':'Category', 'fields':{
				'sequence':{'label':'Sequence', 'type':'text', 'size':'small'},
				'name':{'label':'Name', 'type':'text', 'size':'medium'},
				}},
			'_buttons':{'label':'', 'buttons':{
				'save':{'label':'Save', 'fn':'M.ciniki_sapos_settings.saveExpenseCategory();'},
				'delete':{'label':'Delete', 'fn':'M.ciniki_sapos_settings.deleteExpenseCategory(M.ciniki_sapos_settings.ecatedit.category_id);'},
				}},
		};
		this.ecatedit.fieldValue = function(s, i, d) {
			if( this.data[i] == null ) { return ''; }
			return this.data[i];
		};
		this.ecatedit.addClose('Cancel');

		//
		// The mileage rates settings panel
		//
		this.mrates = new M.panel('Mileage Rates',
			'ciniki_sapos_settings', 'mrates',
			'mc', 'medium', 'sectioned', 'ciniki.sapos.settings.mrates');
		this.mrates.sections = {
			'rates':{'label':'Mileage Rates', 'type':'simplegrid', 'num_cols':3,
				'headerValues':['Rate', 'Start', 'End'],
				'addTxt':'Add',
				'addFn':'M.ciniki_sapos_settings.editMileageRate(\'M.ciniki_sapos_settings.showMileageRates();\',0);',
				}
		};
		this.mrates.sectionData = function(s) { return this.data[s]; }
		this.mrates.cellValue = function(s, i, j, d) {
			switch(j) {
				case 0: return d.rate.rate_display;
				case 1: return d.rate.start_date;
				case 2: return d.rate.end_date;
			}
		};
		this.mrates.rowFn = function(s, i, d) {
			return 'M.ciniki_sapos_settings.editMileageRate(\'M.ciniki_sapos_settings.showMileageRates();\',\'' + d.rate.id + '\');';
		};
		this.mrates.addButton('add', 'Add', 'M.ciniki_sapos_settings.editMileageRate(\'M.ciniki_sapos_settings.showMileageRates();\',0);');
		this.mrates.addClose('Back');

		//
		// The expense category edit panel
		//
		this.mrateedit = new M.panel('Expense Category',
			'ciniki_sapos_settings', 'mrateedit',
			'mc', 'medium', 'sectioned', 'ciniki.sapos.settings.mrateedit');
		this.mrateedit.rate_id = 0;
		this.mrateedit.data = {};
		this.mrateedit.sections = {
			'_rate':{'label':'Mileage Rate', 'fields':{
				'rate':{'label':'Rate/km', 'type':'text', 'size':'small'},
				'start_date':{'label':'Start Date', 'type':'date', 'size':'medium'},
				'end_date':{'label':'End Date', 'type':'date', 'size':'medium'},
				}},
			'_buttons':{'label':'', 'buttons':{
				'save':{'label':'Save', 'fn':'M.ciniki_sapos_settings.saveMileageRate();'},
				'delete':{'label':'Delete', 'fn':'M.ciniki_sapos_settings.deleteMileageRate(M.ciniki_sapos_settings.mrateedit.rate_id);'},
				}},
		};
		this.mrateedit.fieldValue = function(s, i, d) {
			if( this.data[i] == null ) { return ''; }
			return this.data[i];
		};
		this.mrateedit.fieldHistoryArgs = function(s, i) {
			return {'method':'ciniki.sapos.history', 'args':{'business_id':M.curBusinessID,
				'object':'ciniki.sapos.mileage_rate', 'object_id':this.rate_id, 'field':i}};
		};
		this.mrateedit.addClose('Cancel');

		//
		// The paypal settings panel
		//
		this.paypal = new M.panel('Paypal Settings',
			'ciniki_sapos_settings', 'paypal',
			'mc', 'medium', 'sectioned', 'ciniki.sapos.settings.paypal');
		this.paypal.sections = {
			'paypal':{'label':'Paypal', 'fields':{
				'paypal-api-processing':{'label':'Virtual Terminal', 'type':'toggle', 'default':'no', 'toggles':this.toggleOptions},
				}},
			'test':{'label':'Test Credentials', 'fields':{
				'paypal-test-account':{'label':'Account', 'type':'text'},
				'paypal-test-endpoint':{'label':'Endpoint', 'type':'text'},
				'paypal-test-clientid':{'label':'Client ID', 'type':'text'},
				'paypal-test-secret':{'label':'Secret', 'type':'text'},
				}},
			'live':{'label':'Live Credentials', 'fields':{
				'paypal-live-endpoint':{'label':'Endpoint', 'type':'text'},
				'paypal-live-clientid':{'label':'Client ID', 'type':'text'},
				'paypal-live-secret':{'label':'Secret', 'type':'text'},
				}},
			'_buttons':{'label':'', 'buttons':{
				'save':{'label':'Save', 'fn':'M.ciniki_sapos_settings.savePaypal();'},
				}},
		};
		this.paypal.fieldHistoryArgs = function(s, i) {
			return {'method':'ciniki.sapos.settingsHistory', 
				'args':{'business_id':M.curBusinessID, 'setting':i}};
		}
		this.paypal.fieldValue = function(s, i, d) {
			return this.data[i];
		};
		this.paypal.addButton('save', 'Save', 'M.ciniki_sapos_settings.savePaypal();');
		this.paypal.addClose('Cancel');
	}

	//
	// Arguments:
	// aG - The arguments to be parsed into args
	//
	this.start = function(cb, appPrefix, aG) {
		args = {};
		if( aG != null ) { args = eval(aG); }

		//
		// Create the app container if it doesn't exist, and clear it out
		// if it does exist.
		//
		var appContainer = M.createContainer(appPrefix, 'ciniki_sapos_settings', 'yes');
		if( appContainer == null ) {
			alert('App Error');
			return false;
		} 

		if( args.ecats != null && args.ecats == 'yes' ) {
			this.showExpenseCategories(cb);
		} else {
			this.showMenu(cb);
		}
	}

	//
	// Grab the stats for the business from the database and present the list of orders.
	//
	this.showMenu = function(cb) {
		this.menu.sections.invoice.list.qi.visible=(M.curBusiness.modules['ciniki.sapos'].flags&0x04)>0?'yes':'no';
		this.menu.sections.shipments.visible=(M.curBusiness.modules['ciniki.sapos'].flags&0x40)>0?'yes':'no';
		this.menu.sections.expenses.visible=(M.curBusiness.modules['ciniki.sapos'].flags&0x02)>0?'yes':'no';
		this.menu.sections.mileage.visible=(M.curBusiness.modules['ciniki.sapos'].flags&0x100)>0?'yes':'no';
		this.menu.refresh();
		this.menu.show(cb);
	}

	//
	// show the paypal settings
	//
	this.editPaypal = function(cb) {
		M.api.getJSONCb('ciniki.sapos.settingsGet', {'business_id':M.curBusinessID}, function(rsp) {
			if( rsp.stat != 'ok' ) {
				M.api.err(rsp);
				return false;
			}
			var p = M.ciniki_sapos_settings.paypal;
			p.data = rsp.settings;
			p.refresh();
			p.show(cb);
		});
	};

	//
	// Save the Paypal settings
	//
	this.savePaypal = function() {
		var c = this.paypal.serializeForm('no');
		if( c != '' ) {
			M.api.postJSONCb('ciniki.sapos.settingsUpdate', {'business_id':M.curBusinessID}, 
				c, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_sapos_settings.paypal.close();
				});
		} else {
			this.paypal.close();
		}
	};

	//
	// show the invoice settings
	//
	this.editInvoice = function(cb) {
		M.api.getJSONCb('ciniki.sapos.settingsGet', {'business_id':M.curBusinessID}, function(rsp) {
			if( rsp.stat != 'ok' ) {
				M.api.err(rsp);
				return false;
			}
			var p = M.ciniki_sapos_settings.invoice;
			p.data = rsp.settings;
			p.refresh();
			p.show(cb);
		});
	};

	//
	// Save the Invoice settings
	//
	this.saveInvoice = function() {
		var c = this.invoice.serializeForm('no');
		if( c != '' ) {
			M.api.postJSONCb('ciniki.sapos.settingsUpdate', {'business_id':M.curBusinessID}, 
				c, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_sapos_settings.invoice.close();
				});
		} else {
			this.invoice.close();
		}
	};

	//
	// show the shipment settings
	//
	this.editShipment = function(cb) {
		M.api.getJSONCb('ciniki.sapos.settingsGet', {'business_id':M.curBusinessID}, function(rsp) {
			if( rsp.stat != 'ok' ) {
				M.api.err(rsp);
				return false;
			}
			var p = M.ciniki_sapos_settings.shipment;
			p.data = rsp.settings;
			p.refresh();
			p.show(cb);
		});
	};

	//
	// Save the Shipment settings
	//
	this.saveShipment = function() {
		var c = this.shipment.serializeForm('no');
		if( c != '' ) {
			M.api.postJSONCb('ciniki.sapos.settingsUpdate', {'business_id':M.curBusinessID}, 
				c, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_sapos_settings.shipment.close();
				});
		} else {
			this.shipment.close();
		}
	};

	//
	// Quick Invoice Items
	//
	this.showQI = function(cb) {
		M.api.getJSONCb('ciniki.sapos.qiItemList', 
			{'business_id':M.curBusinessID}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_sapos_settings.qi;
				p.data = {'items':rsp.items};
				p.refresh();
				p.show(cb);
			});
	};

	this.editQIItem = function(cb, iid) {
		if( M.curBusiness.modules['ciniki.taxes'] != null ) {
			M.api.getJSONCb('ciniki.taxes.typeList', {'business_id':M.curBusinessID}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_sapos_settings.qiedit;
				p.sections.item.fields.taxtype_id.options[0] = 'No Taxes';
				for(i in rsp.active) {
					p.sections.item.fields.taxtype_id.options[rsp.active[i].type.id] = rsp.active[i].type.name + ((rsp.active[i].type.rates==''||rsp.active[i].type.rates==null)?', No Taxes':', ' + rsp.active[i].type.rates);
				}
				M.ciniki_sapos_settings.editQIItemLoad(cb, iid);
			});
		} else {
			this.editQIItemLoad(cb, iid);
		}
	};

	this.editQIItemLoad = function(cb, qid) {
		if( qid != null ) { this.qiedit.item_id = qid; }
		if( this.qiedit.item_id > 0 ) {
			this.qiedit.sections._buttons.buttons.delete.visible='yes';
			M.api.getJSONCb('ciniki.sapos.qiItemGet', {'business_id':M.curBusinessID,
				'item_id':this.qiedit.item_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					var p = M.ciniki_sapos_settings.qiedit;
					p.data = rsp.item;
					p.refresh();
					p.show(cb);
				});
		} else {
			this.qiedit.reset();
			this.qiedit.data = {};
			this.qiedit.sections._buttons.buttons.delete.visible='no';
			this.qiedit.refresh();
			this.qiedit.show(cb);
		}
	};

	this.saveQIItem = function() {
		if( this.qiedit.item_id > 0 ) {
			var c = this.qiedit.serializeForm('no');
			if( c != '' ) {
				M.api.postJSONCb('ciniki.sapos.qiItemUpdate', {'business_id':M.curBusinessID,
					'item_id':this.qiedit.item_id}, c, function(rsp) {
						if( rsp.stat != 'ok' ) {
							M.api.err(rsp);
							return false;
						}
						M.ciniki_sapos_settings.qiedit.close();
					});
			} else {
				this.qiedit.close();
			}
		} else {
			var c = this.qiedit.serializeForm('yes');
			M.api.postJSONCb('ciniki.sapos.qiItemAdd', {'business_id':M.curBusinessID},
				c, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_sapos_settings.qiedit.close();
				});
		}
	};

	this.deleteQIItem = function(cid) {
		if( confirm('Are you sure you want to remove this category?') ) {
			M.api.getJSONCb('ciniki.sapos.qiItemDelete', {'business_id':M.curBusinessID,
				'item_id':cid}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_sapos_settings.qiedit.close();
				});
		}
	};

	//
	// Expenses
	//
	this.showExpenseCategories = function(cb) {
		M.api.getJSONCb('ciniki.sapos.expenseCategoryList', 
			{'business_id':M.curBusinessID}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_sapos_settings.ecats;
				p.data = {'categories':rsp.categories};
				p.refresh();
				p.show(cb);
			});
	};

	this.editExpenseCategory = function(cb, cid) {
		if( cid != null ) { this.ecatedit.category_id = cid; }
		if( this.ecatedit.category_id > 0 ) {
			this.ecatedit.sections._buttons.buttons.delete.visible='yes';
			M.api.getJSONCb('ciniki.sapos.expenseCategoryGet', {'business_id':M.curBusinessID,
				'category_id':this.ecatedit.category_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					var p = M.ciniki_sapos_settings.ecatedit;
					p.data = rsp.category;
					p.refresh();
					p.show(cb);
				});
		} else {
			this.ecatedit.reset();
			this.ecatedit.data = {};
			this.ecatedit.sections._buttons.buttons.delete.visible='no';
			this.ecatedit.refresh();
			this.ecatedit.show(cb);
		}
	};

	this.saveExpenseCategory = function() {
		if( this.ecatedit.category_id > 0 ) {
			var c = this.ecatedit.serializeForm('no');
			if( c != '' ) {
				M.api.postJSONCb('ciniki.sapos.expenseCategoryUpdate', {'business_id':M.curBusinessID,
					'category_id':this.ecatedit.category_id}, c, function(rsp) {
						if( rsp.stat != 'ok' ) {
							M.api.err(rsp);
							return false;
						}
						M.ciniki_sapos_settings.ecatedit.close();
					});
			} else {
				this.ecatedit.close();
			}
		} else {
			var c = this.ecatedit.serializeForm('yes');
			M.api.postJSONCb('ciniki.sapos.expenseCategoryAdd', {'business_id':M.curBusinessID},
				c, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_sapos_settings.ecatedit.close();
				});
		}
	};

	this.deleteExpenseCategory = function(cid) {
		if( confirm('Are you sure you want to remove this category?') ) {
			M.api.getJSONCb('ciniki.sapos.expenseCategoryDelete', {'business_id':M.curBusinessID,
				'category_id':cid}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_sapos_settings.ecatedit.close();
				});
		}
	};

	//
	// Mileage
	//
	this.showMileageRates = function(cb) {
		M.api.getJSONCb('ciniki.sapos.mileageRateList', 
			{'business_id':M.curBusinessID}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_sapos_settings.mrates;
				p.data = {'rates':rsp.rates};
				p.refresh();
				p.show(cb);
			});
	};

	this.editMileageRate = function(cb, rid) {
		if( rid != null ) { this.mrateedit.rate_id = rid; }
		if( this.mrateedit.rate_id > 0 ) {
			this.mrateedit.sections._buttons.buttons.delete.visible='yes';
			M.api.getJSONCb('ciniki.sapos.mileageRateGet', {'business_id':M.curBusinessID,
				'rate_id':this.mrateedit.rate_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					var p = M.ciniki_sapos_settings.mrateedit;
					p.data = rsp.rate;
					p.refresh();
					p.show(cb);
				});
		} else {
			this.mrateedit.reset();
			this.mrateedit.data = {};
			this.mrateedit.sections._buttons.buttons.delete.visible='no';
			this.mrateedit.refresh();
			this.mrateedit.show(cb);
		}
	};

	this.saveMileageRate = function() {
		if( this.mrateedit.rate_id > 0 ) {
			var c = this.mrateedit.serializeForm('no');
			if( c != '' ) {
				M.api.postJSONCb('ciniki.sapos.mileageRateUpdate', {'business_id':M.curBusinessID,
					'rate_id':this.mrateedit.rate_id}, c, function(rsp) {
						if( rsp.stat != 'ok' ) {
							M.api.err(rsp);
							return false;
						}
						M.ciniki_sapos_settings.mrateedit.close();
					});
			} else {
				this.mrateedit.close();
			}
		} else {
			var c = this.mrateedit.serializeForm('yes');
			M.api.postJSONCb('ciniki.sapos.mileageRateAdd', {'business_id':M.curBusinessID},
				c, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_sapos_settings.mrateedit.close();
				});
		}
	};

	this.deleteMileageRate = function(rid) {
		if( confirm('Are you sure you want to remove this rate?') ) {
			M.api.getJSONCb('ciniki.sapos.mileageRateDelete', {'business_id':M.curBusinessID,
				'rate_id':rid}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_sapos_settings.mrateedit.close();
				});
		}
	};
}
