//
// This panel will create or edit an expense
//
function ciniki_sapos_expense() {
	this.init = function() {
		//
		// The view expense panel
		//
		this.expense = new M.panel('Expense',
			'ciniki_sapos_expense', 'expense',
			'mc', 'medium', 'sectioned', 'ciniki.sapos.expense.expense');
		this.expense.expense_id = 0;
		this.expense.sections = {
			'details':{'label':'', 'list':{
				'name':{'label':'Name'},
				'description':{'label':'Description'},
				'invoice_date':{'label':'Date'},
//				'paid_date':{'label':'Paid Date'},
				}},
			'items':{'label':'', 'type':'simplegrid', 'num_cols':2,
				'cellClasses':['label', ''],
				},
			'totals':{'label':'', 'list':{
				'total_amount':{'label':'Total'},
				}},
			'notes':{'label':'Notes', 'visible':'no', 'type':'htmlcontent'},
			'images':{'label':'Images', 'type':'simplethumbs'},
			'_images':{'label':'', 'type':'simplegrid', 'num_cols':1,
				'addTxt':'Add Image',
				'addFn':'M.startApp(\'ciniki.sapos.expenseimages\',null,\'M.ciniki_sapos_expense.showExpense();\',\'mc\',{\'expense_id\':M.ciniki_sapos_expense.expense.expense_id,\'add\':\'yes\'});',
				},
			'_buttons':{'label':'', 'buttons':{
				'edit':{'label':'Edit', 'fn':'M.ciniki_sapos_expense.showEdit(\'M.ciniki_sapos_expense.showExpense();\',M.ciniki_sapos_expense.expense.expense_id);'},
				}},
		};
		this.expense.sectionData = function(s) {
			if( s == 'details' || s == 'totals' ) { return this.sections[s].list; }
			return this.data[s];
		};
		this.expense.listLabel = function(s, i, d) { return d.label; }
		this.expense.listValue = function(s, i, d) { 
			if( i == 'description' ) { return this.data[i].replace(/\n/g, '<br/>'); }
			return this.data[i]; 
		}
		this.expense.cellValue = function(s, i, j, d) {
			if( s == 'items' ) {
				switch(j) {
					case 0: return d.item.name;
					case 1: return d.item.amount;
				}
			}
		};
		this.expense.fieldValue = function(s, i, d) {
			if( i == 'notes' ) {
				return this.data[i].replace(/\n/g, '<br/>');
			}
		};
		this.expense.thumbSrc = function(s, i, d) {
			if( d.image.image_data != null && d.image.image_data != '' ) {
				return d.image.image_data;
			} else {
				return '/ciniki-manage-themes/default/img/noimage_75.jpg';
			}
		};
		this.expense.thumbTitle = function(s, i, d) {
			if( d.image.name != null ) { return d.image.name; }
			return '';
		};
		this.expense.thumbID = function(s, i, d) {
			if( d.image.id != null ) { return d.image.id; }
			return 0;
		};
		this.expense.thumbFn = function(s, i, d) {
			return 'M.startApp(\'ciniki.sapos.expenseimages\',null,\'M.ciniki_sapos_expense.showExpense();\',\'mc\',{\'expense_image_id\':\'' + d.image.id + '\'});';
		};
		this.expense.addDropImage = function(iid) {
			var rsp = M.api.getJSON('ciniki.sapos.expenseImageAdd',
				{'business_id':M.curBusinessID, 'image_id':iid,
					'expense_id':M.ciniki_sapos_expense.expense.expense_id});
			if( rsp.stat != 'ok' ) {
				M.api.err(rsp);
				return false;
			}
			return true;
		};
		this.expense.addDropImageRefresh = function() {
			if( M.ciniki_sapos_expense.expense.expense_id > 0 ) {
				var rsp = M.api.getJSONCb('ciniki.sapos.expenseGet', {'business_id':M.curBusinessID, 
					'expense_id':M.ciniki_sapos_expense.expense.expense_id, 'images':'yes'}, function(rsp) {
						if( rsp.stat != 'ok' ) {
							M.api.err(rsp);
							return false;
						}
						M.ciniki_sapos_expense.expense.data.images = rsp.expense.images;
						M.ciniki_sapos_expense.expense.refreshSection('images');
					});
			}
		};
		this.expense.addButton('edit', 'Edit', 'M.ciniki_sapos_expense.showEdit(\'M.ciniki_sapos_expense.showExpense();\',M.ciniki_sapos_expense.expense.expense_id);');
		this.expense.addClose('Back');
			
		//
		// The edit expense panel
		//
		this.edit = new M.panel('Expense',
			'ciniki_sapos_expense', 'edit',
			'mc', 'medium mediumaside', 'sectioned', 'ciniki.sapos.expense.edit');
		this.edit.expense_id = 0;
		this.edit.data = {};
		this.edit.sections = {
			'details':{'label':'', 'aside':'left', 'fields':{
				'name':{'label':'Name', 'type':'text', 'autofocus':'yes', 'livesearch':'yes'},
				'description':{'label':'Description', 'type':'text'},
				'invoice_date':{'label':'Date', 'type':'text', 'size':'medium'},
//				'paid_date':{'label':'Paid Date', 'type':'text', 'size':'medium'},
				}},
			'items':{'label':'', 'aside':'right', 'fields':{
				}},
			'_notes':{'label':'Notes', 'aside':'left', 'fields':{
				'notes':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'small'},
				}},
			'_buttons':{'label':'', 'aside':'left', 'buttons':{
				'save':{'label':'Save', 'fn':'M.ciniki_sapos_expense.saveExpense();'},
				'saveadd':{'label':'Save, Add Another', 'fn':'M.ciniki_sapos_expense.saveExpense(\'yes\');'},
				'delete':{'label':'Delete', 'fn':'M.ciniki_sapos_expense.deleteExpense(M.ciniki_sapos_expense.edit.expense_id);'},
				}},
		};
		this.edit.fieldValue = function(s, i, d) {
			if( this.data[i] == null ) { return ''; }
			return this.data[i];
		};
		this.edit.liveSearchCb = function(s, i, v) {
			if( i == 'name' ) {
				M.api.getJSONBgCb('ciniki.sapos.expenseSearch', {'business_id':M.curBusinessID,
					'items':'yes', 'start_needle':v, 'limit':15}, function(rsp) {
						M.ciniki_sapos_expense.edit.searchExpenseResults = rsp.expenses;
						M.ciniki_sapos_expense.edit.liveSearchShow(s,i,M.gE(M.ciniki_sapos_expense.edit.panelUID+'_'+i), rsp.expenses);
					});
			}
		}
		this.edit.liveSearchResultValue = function(s,f,i,j,d) {
			if( f == 'name' && d.expense != null ) {
				return d.expense.name + ' <span class="subdue">' + d.expense.description + '</span>';
			}
			return '';
		};
		this.edit.liveSearchResultRowFn = function(s,f,i,j,d) {
			if( f == 'name' && d.expense != null ) {
				return 'M.ciniki_sapos_expense.edit.updateExpense(\'' + s + '\',\'' + f + '\',' + i + ')';
			}
		};
		this.edit.updateExpense = function(s, fid, expense) {
			var e = M.ciniki_sapos_expense.edit.searchExpenseResults[expense];
			if( e != null && e.expense != null ) {
				this.setFieldValue('name', e.expense.name);
				this.setFieldValue('description', e.expense.description);
				if( e.expense.items != null ) {
					for(i in e.expense.items) {
						var el = M.gE(M.ciniki_sapos_expense.edit.panelUID + '_category_' + e.expense.items[i].item.category_id);
						if( el != null ) {
							this.setFieldValue('category_' + e.expense.items[i].item.category_id, e.expense.items[i].item.amount_display);
						}
					}
				}
				this.removeLiveSearch(s, fid);
			}
		};
		this.edit.fieldHistoryArgs = function(s, i) {
			if( s == 'items' ) {
				return {'method':'ciniki.sapos.history', 'args':{'business_id':M.curBusinessID,
					'object':'ciniki.sapos.expense_item', 'object_id':this.expense_id, 'field':i}};
			}
			return {'method':'ciniki.sapos.history', 'args':{'business_id':M.curBusinessID,
				'object':'ciniki.sapos.expense', 'object_id':this.expense_id, 'field':i}};
		};
		this.edit.addButton('save', 'Save', 'M.ciniki_sapos_expense.saveExpense();');
		this.edit.addClose('Cancel');
	}; 

	this.start = function(cb, aP, aG) {
		var args = {};
		if( aG != null ) { args = eval(aG); }
		var aC = M.createContainer(aP, 'ciniki_sapos_expense', 'yes');
		if( aC == null ) {
			alert('App Error');
			return false;
		}

		M.api.getJSONCb('ciniki.sapos.expenseCategoryList', {'business_id':M.curBusinessID}, function(rsp) {
			if( rsp.stat != 'ok' ) {
				M.api.err(rsp);
				return false;
			}
			var p = M.ciniki_sapos_expense.edit;
			p.sections.items.fields = {};
			for(i in rsp.categories) {
				p.sections.items.fields['category_' + rsp.categories[i].category.id] = {
					'label':rsp.categories[i].category.name,
					'type':'text', 'size':'small'
					};
			}
			if( args.expense_id != null && args.expense_id != '' && args.expense_id > 0 ) {
				M.ciniki_sapos_expense.showExpense(cb, args.expense_id);
			} else {
				M.ciniki_sapos_expense.showEdit(cb, 0);
			}
		});
	};

	this.showExpense = function(cb, eid) {
		if( eid != null ) { this.expense.expense_id = eid; }
		if( this.expense.expense_id > 0 ) {
			M.api.getJSONCb('ciniki.sapos.expenseGet', {'business_id':M.curBusinessID,
				'expense_id':this.expense.expense_id, 'images':'yes'}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					var p = M.ciniki_sapos_expense.expense;
					p.data = rsp.expense;
					p.sections.details.list.description.visible=((rsp.expense.description!='')?'yes':'no');
//					p.sections.details.list.paid_date.visible=((rsp.expense.paid_date!='')?'yes':'no');
					p.refresh();
					p.show(cb);
				});
		}
	};

	this.showEdit = function(cb, eid, date) {
		if( eid != null ) { this.edit.expense_id = eid; }
		if( this.edit.expense_id > 0 ) {
			this.edit.sections._buttons.buttons.delete.visible = 'yes';
			this.edit.sections._buttons.buttons.saveadd.visible = 'no';
			M.api.getJSONCb('ciniki.sapos.expenseGet', {'business_id':M.curBusinessID,
				'expense_id':this.edit.expense_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					var p = M.ciniki_sapos_expense.edit;
					p.data = rsp.expense;
					for(i in rsp.expense.items) {
						p.data['category_' + rsp.expense.items[i].item.category_id] = rsp.expense.items[i].item.amount;
					}
					p.refresh();
					p.show(cb);
				});
		} else {
			var p = M.ciniki_sapos_expense.edit;
			this.edit.sections._buttons.buttons.saveadd.visible = 'yes';
			this.edit.sections._buttons.buttons.delete.visible = 'no';
			p.reset();
			p.data = {};
			if( date == null || date == '' ) {
				var dt = new Date();
				p.data.invoice_date = M.dateFormat(dt);
			} else {
				p.data.invoice_date = date;
			}
			p.sections._buttons.buttons.delete.visible = 'no';
			p.refresh();
			p.show(cb);
		}
	};

	this.saveExpense = function(add) {
		if( this.edit.expense_id > 0 ) {
			var c = this.edit.serializeForm('no');
			if( c != '' ) {
				M.api.postJSONCb('ciniki.sapos.expenseUpdate', {'business_id':M.curBusinessID,
					'expense_id':this.edit.expense_id}, c, function(rsp) {
						if( rsp.stat != 'ok' ) {
							M.api.err(rsp);
							return false;
						}
						M.ciniki_sapos_expense.edit.close();
					});
			} else {
				this.edit.close();
			}
		} else {
			var c = this.edit.serializeForm('yes');
			M.api.postJSONCb('ciniki.sapos.expenseAdd', 
				{'business_id':M.curBusinessID}, c, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					if( add == 'yes' ) { M.ciniki_sapos_expense.showEdit(null,0); }
					else { M.ciniki_sapos_expense.edit.close(); }
				});
		}
	};

	this.deleteExpense = function(eid) {
		if( eid <= 0 ) { return false; }
		if( confirm("Are you sure you want to remove this expense?") ) {
			M.api.getJSONCb('ciniki.sapos.expenseDelete', {'business_id':M.curBusinessID,
				'expense_id':eid}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_sapos_expense.expense.close();
				});
		}
	};
}
