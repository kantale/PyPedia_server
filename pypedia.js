
			function pyp_get_execution_command(clicked_id) {
						var elem = document.getElementById('parameters_form').elements;
						var params = '';
						var article_name = '';
						var username = '';
						var password = '';
						var filenames = [];
						for(var i = 0; i < elem.length; i++) {
							prefix = elem[i].name.substring(0, 4);
							if (elem[i].name == 'article_title') {
								article_name = elem[i].value;
							}
							else if (elem[i].name == 'pyp_username') {
								username = elem[i].value;
							}
							else if (prefix == 'data' || prefix == 'selc') {
								params += '\'' + elem[i].name.substring(6) + '\'' + ' : ' + '\'' +  elem[i].value + '\',';
							}
							else if (prefix == 'file') {
								params += '\'' + elem[i].name.substring(6) + '\'' + ' : ' + '\'' +  elem[i].value.replace(/^.*[\\\/]/, '') + '\',';
							}
							else if (prefix == 'eval') {
								var elem_value = elem[i].value;
								if (elem_value == "") {
									elem_value = "None"
								}
								params += '\'' + elem[i].name.substring(6) + '\'' + ' : ' + elem_value + ',';
							}
						}
						if (clicked_id == 'eob') {
							return 'print "Output:<pre>"\n_=' + article_name + '(**{' + params + '})\nprint "</pre>"\nif _ != None: print "Returned:<pre>%s</pre>" % str(_)';
						}
						if (clicked_id == 'dc') {
							return '_=' + article_name + '(**{' + params + '})\nif _ != None: print "Method returned:\\n%s" % str(_)';
						}
						if (clicked_id == 'eorcg') {
							return [article_name, username, params];
						}
			}

			function upload_file(files, filename) {
				//Get username, password
				var par_elem = document.getElementById('parameters_form');
				var elem = par_elem.elements;
				var username = '';
				var password = '';
				for (var i = 0; i < elem.length; i++) {
					if (elem[i].name == 'pyp_username') {username = elem[i].value;}
				}

				pw_elem = document.getElementById('password_input');
				if (!pw_elem) {
					pyp_show_message("To upload a file, first you need to enter the password to the remote computer. Press 'Execute on remote computer'to enter the password", 'cc3333');
					par_elem.innerHTML += "  ";
					return;
				}
				
				password = pw_elem.value;
	
				if (files.length === 0) {return;}
				var file = files[0];

				pyp_show_message('Uploading file: ' + filename + " ...", 'f4faff');

				oFReader = new FileReader();
				oFReader.onloadend = function(oFREvent) {
					request = $.ajax({
						url: 'index.php',
						data: {ul_file : encodeURIComponent(filename), username : encodeURIComponent(username), password : encodeURIComponent(password), data : oFREvent.target.result},
						success: (function (data){pyp_show_message(data, 'f4faff');}),
						error: (function(){pyp_show_message('Could not read file: ' + filename, 'cc3333');}),
						dataType: 'text',
						type: 'post'
					});
				};
				oFReader.readAsDataURL(file);
			}

			function pyp_show_message(message, bgr_color) {
				var results_div_el = document.getElementById('results_div');
				var pw_div_el = document.getElementById('pw_div');
				var parameters_form_el = document.getElementById('parameters_form');
				if (pw_div_el) {
					add_after = pw_div_el;
				}
				else {
					add_after = parameters_form_el;
				}

				if (results_div_el) parameters_form_el.parentNode.removeChild(results_div_el);

				var new_div = document.createElement("div");
				new_div.id = "results_div";
				new_div.innerHTML = '<pre style="background:#' + bgr_color + ';">' + message + '</pre>';
				add_after.parentNode.insertBefore(new_div, add_after.nextSibling);
			}

			function pyp_manage_form(clicked_id) {
					if (clicked_id == 'dc') {
						var command = pyp_get_execution_command(clicked_id);
						var auri = 'index.php?dl_code=' + encodeURIComponent(command);
						window.open(auri, '_blank');
					}
					else if (clicked_id == 'eorc') {
						if (!document.getElementById('password_input')) {
							var new_div = document.createElement("div");
							new_div.id = 'pw_div';
							var to_add = 'Enter password: <input type="password" id="password_input" name="pyp_password"/><input type="button" value="GO!" id="eorcg" onClick="pyp_manage_form(this.id)"/>';
							new_div.innerHTML = to_add;
							var parameters_form = document.getElementById('parameters_form');
							parameters_form.parentNode.insertBefore(new_div, parameters_form.nextSibling);
//							document.getElementById('parameters_form').parentNode.innerHTML += to_add;
//							document.getElementById('parameters_form').innerHTML += to_add;
                      				}
					}
					else if (clicked_id == 'eorcg') {
						
						var form_elems = pyp_get_execution_command(clicked_id);
						//var auri = 'index.php?ssh_code=' + encodeURIComponent(form_elems[0]) + "&username=" + encodeURIComponent(form_elems[1]) + "&password=" + encodeURIComponent(form_elems[2]) +  "&params=" + encodeURIComponent(form_elems[3]);
						//pyp_show_message(auri, 'f5faff');
						var password = document.getElementById('password_input').value;

						pyp_show_message('Submitting ..', 'f4faff');

						request = $.ajax({
							url: 'index.php',
							data: {ssh_code : encodeURIComponent(form_elems[0]), username : encodeURIComponent(form_elems[1]), password : encodeURIComponent(password), params : encodeURIComponent(form_elems[2])},
							success: (function (data){pyp_show_message(data, 'f4faff');}),
							//success: (function() {}),
							error: (function(){pyp_show_message('Could not execute REST api', 'cc3333');}),
							dataType: 'text',
							type: 'post'
						});
					}
					else if ((clicked_id == 'eob') || clicked_id == 'eobm') {
						var command = '';
						if (clicked_id == 'eob') command = pyp_get_execution_command(clicked_id);
						else command = myCodeMirror.getValue();
						request = $.ajax({
							url: 'index.php',
							data: {get_code : command } ,
							success: (function (data) {
								request2 = $.ajax({
									url: 'http://pypediacode.appspot.com',
									data: encodeURIComponent(data),
									success: (function (data){pyp_show_message(data['text'], 'f5faff');}),
									error: (function(){pyp_show_message('Could not contact appspot', 'cc3333');}),
									dataType: 'json',
									type: 'post'
								});
							}),
							error:   (function (    ) {pyp_show_message('Could not contact PyPedia REST interface', 'cc3333');}),
							dataType: 'text',
							type: 'post'
							});

					}
					else if (clicked_id == 'fa') {
						var elem = document.getElementById('header_form').elements;
						article_name = '';
						for(var i = 0; i < elem.length; i++) {
							if (elem[i].name == 'article_title') {
                                                                article_name = elem[i].value;
                                                        }

						}
						var auri = 'index.php?fork=' + encodeURIComponent(article_name);
						//window.open(auri);
						window.location = auri;
					}
				}

