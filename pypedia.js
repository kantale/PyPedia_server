			function pyp_get_execution_command(clicked_id) {
						var elem = document.getElementById('parameters_form').elements;
						var params = '';
						var article_name = '';
						var username = '';
						var password = '';
						var filenames = [];
						var download_code = '';
						var execute_command = '';
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
								download_code += '    ' + elem[i].name.substring(6) + ' = ' + '\'' +  elem[i].value + '\'\n';
								execute_command +=  elem[i].name.substring(6) + '=' + elem[i].name.substring(6) + ', ';
							}
							else if (prefix == 'file') {
								var filename = elem[i].value.replace(/^.*[\\\/]/, '');
								params += '\'' + elem[i].name.substring(6) + '\'' + ' : ' + '\'' + filename + '\',';
								download_code += '     ' + elem[i].name.substring(6) + ' = ' + '\'' + filename + '\'\n';
								execute_command += elem[i].name.substring(6) + '=' + elem[i].name.substring(6) + ', ';
								filenames.push(filename);
							}
							else if (prefix == 'eval') {
								var elem_value = elem[i].value;
								if (elem_value == "") {
									elem_value = "None"
								}
								params += '\'' + elem[i].name.substring(6) + '\'' + ' : ' + elem_value + ',';
								download_code += '    ' + elem[i].name.substring(6) + ' = ' + elem_value + '\n';
								execute_command += elem[i].name.substring(6) + '=' + elem[i].name.substring(6)  + ', ';
							}
						}
						if (clicked_id == 'eob') {
							return 'print "Output:<pre>"\n_=' + article_name + '(**{' + params + '})\nprint "</pre>"\nif _ != None: print "Returned:<pre>%s</pre>" % str(_)';
						}
						if (clicked_id == 'dc') {
							var to_ret = '\n\n#Method name =' + article_name + '()\n';
							to_ret += "if __name__ == '__main__':\n";
							to_ret += '    print __pypdoc__\n';
							to_ret += download_code + '\n';
							to_ret += '    returned = ' + article_name + '(' + execute_command.substring(0, execute_command.length - 2) + ')\n';
							to_ret += '    if returned:\n';
							to_ret += '        print \'Method returned:\'\n';
							to_ret += '        print str(returned)\n';

							return to_ret;

							//return '_=' + article_name + '(**{' + params + '})\nif _ != None: print "Returned:\\n%s" % str(_)\n';
						}
						if (clicked_id == 'eorcg') {
							return [article_name, username, params, filenames];
						}
			}

			function upload_file(files, filename) {
				//Get username, password
				var par_elem = document.getElementById('parameters_form');
				var elem = par_elem.elements;
				var username = '';
				for (var i = 0; i < elem.length; i++) {
					if (elem[i].name == 'pyp_username') {username = elem[i].value;}
				}

				if (files.length === 0) {return;}
				var file = files[0];

				pyp_show_message('Uploading file: ' + filename + " locally ...", 'f4faff');

				oFReader = new FileReader();
				oFReader.onloadend = function(oFREvent) {
					request = $.ajax({
						url: 'index.php',
						data: {ul_file : encodeURIComponent(filename), username : encodeURIComponent(username), data : oFREvent.target.result},
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

			function pyp_execute_remote(article_name, username, password, params) {
				pyp_show_message('Executing remotely ..', 'f4faff');
				request = $.ajax({
					url: 'index.php',
					data: {ssh_code : encodeURIComponent(article_name), username : encodeURIComponent(username), password : encodeURIComponent(password), params : encodeURIComponent(params)},
					success: (function (data){pyp_show_message(data, 'f4faff');}),
					error: (function(){pyp_show_message('Could not execute REST api', 'cc3333');}),
					dataType: 'text',
					type: 'post'
				});
			}

			function pyp_upload_remote(filenames, username, password, cur_index, max_index, article_name, params) {
				pyp_show_message('Uploading file: ' + filenames[cur_index] + " remotely ...", 'f4faff');
				request = $.ajax({
					url: 'index.php',
					data: {ul_remote: encodeURIComponent(filenames[cur_index]), username : encodeURIComponent(username), password : encodeURIComponent(password)},
					success: (function (data){
							pyp_show_message(data, 'f4faff');
							if (cur_index+1 < max_index) {
								pyp_upload_remote(filenames, username, password, cur_index+1, max_index);
							}
							else {
								pyp_execute_remote(article_name, username, password, params);
							}
						}),
					error: (function(){pyp_show_message('Could not upload file: ' + filenames[cur_index] + ' remotely', 'cc3333');}),
					dataType: 'text',
					type: 'post'
				});
			}

			function pyp_manage_form(clicked_id) {
					if (clicked_id == 'dc') {
						var command = pyp_get_execution_command(clicked_id);
						var auri = 'index.php?dl_code=' + encodeURIComponent(command);
						auri = auri.replace('!', '%21');
						var local_uri = 'http://www.pypedia.com/' + auri;
						pyp_show_message('URL: <a href="' + local_uri + '">' + local_uri + '</a>\nwget command: wget -O code.py "' + local_uri + '"', 'f5faff');
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
                      				}
					}
					else if (clicked_id == 'eorcg') {
						
						var form_elems = pyp_get_execution_command(clicked_id);
						var password = document.getElementById('password_input').value;

						//Upload files from local to remote
						var filenames = form_elems[3];
						pyp_upload_remote(filenames, form_elems[1], password, 0, filenames, form_elems[0], form_elems[2]);
					}
					else if (clicked_id == 'create_l') {
						var code = myCodeMirror.getValue(); 
						var url = 'http://www.pypedia.com/index.php?input_code=' + encodeURIComponent(code);
						var message = '<a href="' + url + '">' + url + '</a>';
						pyp_show_message(message);
					}
					else if ((clicked_id == 'eob') || clicked_id == 'eobm') {
						var command = '';
						if (clicked_id == 'eob') command = pyp_get_execution_command(clicked_id);
						else command = myCodeMirror.getValue();
						pyp_show_message('Submitting to python sandbox. Please wait..', 'f5faff');
						request = $.ajax({
							url: 'index.php',
							data: {get_code : command } ,
							success: (function (data) {
								request2 = $.ajax({
									//url: 'http://pypediacode.appspot.com',
									//url: 'http://83.212.107.58:8080',
									url: 'http://83.212.107.58',
									data: encodeURIComponent(data),
									success: (function (data){pyp_show_message(data['text'], 'f5faff');}),
									error: (function(){pyp_show_message('Could not contact sandbox', 'cc3333');}),
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

