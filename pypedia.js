
			function pyp_get_execution_command(clicked_id) {
						var elem = document.getElementById('parameters_form').elements;
						var params = '';
						var article_name = '';
						for(var i = 0; i < elem.length; i++) {
							prefix = elem[i].name.substring(0, 4);
							if (elem[i].name == 'article_title') {
								article_name = elem[i].value;
							}
							else if (prefix == 'data' || prefix == 'selc') {
								params += '\'' + elem[i].name.substring(6) + '\'' + ' : ' + '\'' +  elem[i].value + '\',';
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
			}

			function pyp_show_message(message, bgr_color) {
				var results_pre_el = document.getElementById('results_pre');
				var parameters_form_el = document.getElementById('parameters_form');
				if (results_pre_el) parameters_form_el.removeChild(results_pre_el);
				parameters_form_el.innerHTML += '<pre id="results_pre" style="background:#' + bgr_color + ';">' + message + '</pre>' ;
			}

			function pyp_manage_form(clicked_id) {
					if (clicked_id == 'dc') {
						var command = pyp_get_execution_command(clicked_id);
						var auri = 'index.php?dl_code=' + encodeURIComponent(command);
						window.open(auri, '_blank');
					}
					else if (clicked_id == 'eorc') {
						if (!document.getElementById('password_input')) {
							document.getElementById('parameters_form').innerHTML += 'Enter password: <input type="password" id="password_input" name="pyp_password"/><input type="submit" value="GO!" name="pyp_execute"/>';
                      				}
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

