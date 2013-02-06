
			function import_gist(button_id) {
				var div_mb = document.getElementById('message_board');
				div_mb.innerHTML = 'Checking if user is logged in..';
                                request = $.ajax({
                                        url: 'index.php',
                                        data: {'is_logged_in' : 'q'},
                                        success: (function (data){
						if (data == 'True') {
							div_mb.innerHTML = '';
							import_gist_2(button_id);
						}
						else {
							div_mb.innerHTML = 'Error: You need to log in to import a script';
						}
						}),
                                        error: (function(){div_mb.innerHTML = 'Error: Could not contact REST API';}),
                                        dataType: 'text',
                                        type: 'post'
                                });
			}

			function import_gist_2(button_pressed) {
				var url_textbox = '';
				if (button_pressed != 'import_gist_button_nourl') {
					url_textbox += "URL: <input type='text' value='' id='text_gist_url' size='50'/><p>";
				}
				url_textbox += " Main object's name: <input type='text' value='' id='text_gist_name'/> this should be the main importable object existing in your code. If this object is not callable you will not be able to run this method online within PyPedia. The created page will be named after this object and your username.<p>";
				url_textbox += "  Documentation (you can use wikitext): <textarea id='ta_gist_doc' rows='4' cols='50'></textarea><p>";
				url_textbox += "  Insert python functions that have to return True if your class/function works properly (Unitests): <textarea id='ta_gist_uni' rows='8' cols='50'>def test_1(): \n    return True</textarea><p>";
				url_textbox += "  Number of parameters: <select id='combo_gist_param_n' onchange='change_gist_param_n()''>";
				url_textbox += '<option value=""></options>\n';
				for (var i=0; i<=10; i++) {
					url_textbox += '<option value="' + i + '">' + i + '</option>\n';
				}
				url_textbox += '</select> (if this is not a function, then select 0)<p>';

				var div_ig = document.getElementById('ig');
				div_ig.innerHTML = url_textbox;

			}

			function change_gist_param_n() {
				var div_ig = document.getElementById('ig');
				var combo_gist_param_n = document.getElementById('combo_gist_param_n');
				var selected_index = combo_gist_param_n.selectedIndex;
				var selected_value = combo_gist_param_n.options[selected_index].value;
				var gist_parameters_div = document.getElementById('gist_parameters_div');
				if (gist_parameters_div) {
					div_ig.removeChild(gist_parameters_div);
				}
				var gist_parameters_div = document.createElement("div");
				gist_parameters_div.id = 'gist_parameters_div';

				if (selected_index > 1) {gist_parameters_div.innerHTML = "PyPedia will try to create a HTML form for this function's parameters. Variables that are defined as 'expression', will be evaluated (with the 'eval' function). Otherwise they will be passed as string values.<p>";}
				for (var i=0; i<selected_index-1; i++) {
					gist_parameters_div.innerHTML += 'Parameter name: <input type="text" id="gist_param_name_' + i + '"/>';
					gist_parameters_div.innerHTML += ' Type: <select id="gist_param_type_' + i + '"><option value="expression">expression</option><option value="string">string</option></select>';
					gist_parameters_div.innerHTML += ' Default: <input type="text" id="gist_param_def_' + i + '"/>';
					gist_parameters_div.innerHTML += ' Label: <input type="text" id="gist_param_label_' + i + '"/>';
					gist_parameters_div.innerHTML += '<p>';
				}
				gist_parameters_div.innerHTML += '<input type="button" value="Import!" id="import_gist_button2" onClick="import_gist_f()"/>\n';
				div_ig.insertBefore(gist_parameters_div, null);

				//div_ig.innerHTML += selected_value;
				combo_gist_param_n = document.getElementById('combo_gist_param_n');
				combo_gist_param_n.value = selected_value;
			}

			function import_gist_f() {
				//Create a JSON object with all parameters
				var text_gist_url = document.getElementById('text_gist_url');
				var code;
				if (text_gist_url) {
					code = text_gist_url.value;
				}
				else {
					code = myCodeMirror.getValue(); 
				}
				var json_gist = {
					'gist_url' : encodeURIComponent(code),
					'open_url' : (text_gist_url) ? '1' : '0',
					'n_params' : encodeURIComponent(Math.max(0, document.getElementById('combo_gist_param_n').selectedIndex-1)), 
					'fun_name' : encodeURIComponent(document.getElementById('text_gist_name').value),
					'doc'      : encodeURIComponent(document.getElementById('ta_gist_doc').value),
					'uni'      : encodeURIComponent(document.getElementById('ta_gist_uni').value)
				};
				params = [];
				for (var i=0; i<json_gist['n_params']; i++) {
					json_gist['name_' + i] = encodeURIComponent(document.getElementById('gist_param_name_' + i).value);
					json_gist['type_' + i] = encodeURIComponent(document.getElementById('gist_param_type_' + i).selectedIndex);
					json_gist['value_' + i] = encodeURIComponent(document.getElementById('gist_param_def_' + i).value);
					json_gist['label_' + i] = encodeURIComponent(document.getElementById('gist_param_label_' + i).value);
				}

				var div_mb = document.getElementById('message_board');
				div_mb.innerHTML = 'Sending..';

				request = $.ajax({
					url: 'index.php',
					data: json_gist,
					success: (function (data){ div_mb.innerHTML = data;}),
					error: (function(){div_mb.innerHTML = 'Error';}),
					dataType: 'text',
					type: 'post'
				});

			}



