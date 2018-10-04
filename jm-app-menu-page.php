<?php
$app_config = jmapp_read_menu_file();

// needed data:
// default_image_url, drawer_header_url
// drawer_menu_items, home_menu_items
// for each menu item: title, description, icon_name (drawer), image_url (home), provider -> arguments or tabs

?>

<!-- get vue -->
<script src="https://cdn.jsdelivr.net/npm/vue"></script>
<!-- axios -->
<!-- <script src="https://unpkg.com/axios/dist/axios.min.js"></script> -->

<style>
	.jmapp {font-size:12pt;}
	.jmapp h3 {font-size:1.6em;margin:0;margin-top:60px;line-height:1;border-bottom:4px solid #111;}
	.jmapp h4 {font-size:1.4em;margin:0;margin-top:40px;line-height:1;}
	.jmapp h5 {font-size:1.2em;margin:0;margin-top:20px;line-height:1;}
	.jmapp img {width:100%;object-fit:cover;}
	.jmapp-right-panel {width:400px;height:760px;position:fixed;box-sizing:border-box;z-index:9;right:30px;top:40px;}
	.jmapp-device-preview {background:black;border-radius:10px;width:360px;height:640px;box-sizing:bounding-box;padding:60px 20px 60px;}
	.jmapp-device-scaffold {position:relative;width:360px;height:640px;background:white;overflow:hidden;}
	.jmapp-device-appbar {position:relative;width:360px;height:60px;background:#ade;}
	.jmapp-device-content {position:relative;width:360px;height:580px;background:#eff;}
	.jmapp-device-drawer {position:absolute;left:0;width:260px; height:640px; background:#eef;top:0;transition:left 0.7s;overflow:scroll;}
	.jmapp-device-drawer-closed {left:-260px;}
	.jmapp-device-drawer-header {background:#116;height:170px;}
	.jmapp-device-drawer-header img {height:170px;object-fit:cover;}
	.jmapp-device-drawer-section {margin-top:0px;}
	.jmapp-device-drawer-section~.jmapp-device-drawer-section {margin-top:20px;}
	.jmapp-device-drawer-section-item {font-size:12pt;line-height:1;padding:20px 5px 5px;background:rgba(0,0,0,.03);border-bottom:1px solid #ddd;}
	.jmapp-device-drawer-item {font-size:12pt;line-height:1;padding:15px 5px 5px 10px;}
	.jmapp-device-drawer-icon {display:inline-block;vertical-align:middle;margin-right:5px;}
	.drawer-menu-item {border-bottom:3px solid;padding-bottom:40px;}
	
	.drawer-item-tab{margin-bottom:30px;}
	
	.jmapp-device-content {padding:15px;box-sizing:border-box;overflow:scroll;}
	.jmapp-device-home-item {position:relative;width:100%;height:170px;margin-bottom:15px;}
	.jmapp-device-home-item img {width:100%;height:100%;object-fit:cover;}
	.jmapp-device-home-item .title {position:absolute;bottom:0;color:white;box-sizing:border-box;padding:5px;background:rgba(0,0,0,.5);width:100%;height:40px;font-size:10pt;line-height:40px;}
	
	.menu-icon {display: inline-block;height:60px;width:50px;padding-top:13px;}
	.menu-icon div {width:30px;height:3px;margin:5px auto 0;background:white;}
	.appbar-title {display:inline-block;position:absolute;margin-left:10px;height:60px;line-height:60px;font-size:16pt;color:white;}
	
	.jmapp-left-panel {margin-right:440px;}
	.jmapp .input-row {margin-top:20px;width:100%;font-size:1.1em;padding:6px;padding:10px; box-sizing:border-box;border-radius:3px;}
	.jmapp .input-row small {display:block;font-style:italic;font-size:.8em;text-align:center;}
	.jmapp input[type=text] {font-size:1.4em;width:100%;}
	.jmapp input[type=text].title {font-size:2em;}
	.jmapp label{display:inline-block;margin-bottom:10px;}
	.jmapp select{font-size:16pt;}
	.jmapp_delete_button, .jmapp_collapse_button {float:right;}
	.jmapp_button {cursor:pointer;}
	.jmapp .action_buttons {float:right;}
	.jmapp .section {background:rgba(0,0,0,.05);padding:10px 10px 10px 20px;}
	.jmapp .instructions {font-size:.9em;font-style:italic;}
	.collapsible {transition: height 1s;overflow:hidden;}
	.collapsed {height:0px;}
	.clear {clear:both;}
	
	#drawer-menu .section {margin-bottom:20px;}
	.jmapp .drawer-menu-item {margin-bottom:20px;}
	.jmapp .selectable:hover {outline:4px solid rgba(128,128,255,.5);}
	.jmapp .selected {background: rgba(128,128,255,.2); outline:4px solid rgba(128,128,255,.5);}
</style>

<script>
	function toggleDrawer() {
		document.getElementById('jmapp-device-drawer').classList.toggle('jmapp-device-drawer-closed');
	}
</script>

<div class="wrap jmapp">
	
	<h2>Jeff Mikels' App Menu Generator</h2>
	<p>Set up your mobile app using this easy menu generator.</p>
	<p>Your config file will be saved to <a href="<?php echo site_url(jmapp_MENU_FILE); ?>"><?php echo site_url(jmapp_MENU_FILE); ?></a></p>
	<p>Click an item in the app preview or a heading below to show the relevant options.</p>
	
	<div id="jmapp-app">
		
		<!-- APP DEMO -->
		<div class="jmapp-right-panel">
			<div class="jmapp-device-preview">
				<div class="jmapp-device-scaffold">
					<div class="jmapp-device-appbar" onClick="toggleDrawer()">
						<div class="menu-icon">
							<div></div>
							<div></div>
							<div></div>
						</div>
						<div class="appbar-title">Home Page</div>
					</div>
					<div class="jmapp-device-content">
						<div v-for="item in app_config.home_menu.items"
							class="jmapp-device-home-item selectable"
							:class="{selected:item.selected}"
							@click="select(item)">
							<img :src="item.image" alt="">
							<div class="title">{{item.title}}</div>
						</div>
					</div>
					<div id='jmapp-device-drawer' class="jmapp-device-drawer">
						<div class="jmapp-device-drawer-header">
							<img :src="app_config.drawer_menu.drawer_header_url">
						</div>
						<div v-for="section in app_config.drawer_menu.sections" class="jmapp-device-drawer-section">
							<div class="jmapp-device-drawer-section-item selectable"
								:class="{selected:section.selected}"
								@click="select(section)">
								{{section.title}}
							</div>
							<div v-for="item in section.items" class="jmapp-device-drawer-item selectable"
								:class="{selected:item.selected}"
								@click="select(item)">
								<div class="jmapp-device-drawer-icon">
									<i class="material-icons">{{item.icon}}</i>
								</div>
								{{item.title}}
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	
	
		<!-- APP SETTINGS -->
		<div class="jmapp-left-panel">
			<h3>Default Image Settings</h3>
			<small>Tell your app what images to use as its "default" images.</small>
		
			<div class="section">
			
				<div id="default-image" class="input-row">
					<label for="jmapp-default-image-url">
						Default Image
					</label>
					<input id="jmapp-default-image-url" type="text" v-model="app_config.default_image_url"><br />
					<small>used by the app whenever an image can't be loaded for some reason.</small>
				</div>
				<div v-if="app_config.default_image_url" id="default-image-preview" class="input-row">
					<img :src="app_config.default_image_url" alt="">
				</div>
	
				<div id="drawer-header" class="input-row">
					<label for="jmapp-drawer-header-url">
						Drawer Header Image
					</label>
					<input id="jmapp-drawer-header-url" type="text" v-model="app_config.drawer_menu.drawer_header_url">
					<small>placed at the top of the menu drawer.</small>
				</div>
				<div v-if="app_config.drawer_menu.drawer_header_url" id="drawer-header-image-preview" class="input-row">
					<img :src="app_config.drawer_menu.drawer_header_url" alt="">
				</div>
			</div>
	
			<h3>Drawer Sections</h3>
			<small>Click a section to change settings.</small>
			<div id="drawer-menu">
				<div v-for="(section, section_index) in app_config.drawer_menu.sections" class="drawer-menu-section section"
					:class="{selected:section.selected}">
					<div class="action_buttons">
						<span class="jmapp_button jmapp_delete_button" @click="removeSection(section_index)">
							<i class="material-icons">delete</i>
						</span>
						<span class="jmapp_button" style="width:20px;">&nbsp;</span>
						<span class="jmapp_button jmapp_move" @click="moveUp(app_config.drawer_menu.sections, section_index)">
							<i v-if="(section_index > 0)"  class="material-icons">arrow_upward</i>
						</span>
						<span class="jmapp_button jmapp_move" @click="moveDown(app_config.drawer_menu.sections, section_index)">
							<i v-if="(section_index < app_config.drawer_menu.sections.length)"  class="material-icons">arrow_downward</i>
						</span>
					</div>
					<h4 class="selectable"
						@click="select(section)">"{{section.title}}"</h4>
					
					<div class="input-row">
						<label for="">Section Title: </label>
						<input class="title" type="text" v-model="section.title">
					</div>
					
					<div class="instructions">
						Each menu section can have multiple items under it.
					</div>
					
					<div v-for="(item, item_index) in section.items" class="drawer-menu-item" v-if="section.selected">
						<div class="action_buttons">
							<span class="jmapp_button jmapp_delete_button" @click="removeItem(section, item_index)">
								<i class="material-icons">delete</i>
							</span>
							<span class="jmapp_button" style="width:20px;">&nbsp;</span>
							<span class="jmapp_button jmapp_move" @click="moveUp(section.items, item_index)">
								<i v-if="(item_index > 0)"  class="material-icons">arrow_upward</i>
							</span>
							<span class="jmapp_button jmapp_move" @click="moveDown(section.items, item_index)">
								<i v-if="(item_index < section.items.length)"  class="material-icons">arrow_downward</i>
							</span>
						</div>
						
						<h4 class="selectable"
							@click="select(item)">{{section.title}} &gt; {{item.title}}</h4>
							
						<div class="collapsible clear" :class="{selected:item.selected}" v-if="item.selected">
							<div class="input-row">
								<label for="">Item Title: </label>
								<input type="text" v-model="item.title">
							</div>
							<div class="input-row">
								<label for="">Item Icon: </label>
								<input type="text" v-model="item.icon">
								<small>icon may be the textual name of any <a href="https://material.io/tools/icons/?style=baseline">Material Icon</a></small>
							</div>
						
							<h4>{{section.title}} &gt; {{item.title}} • Provider(s)</h4>
							<small v-if="item.tabs.length > 0 && providers[item.tabs[0].provider].tabbable">This Provider can contain multiple tabs. Additional providers will display as additional tabs.</small>
							<div v-for="(tab, tab_index) in item.tabs" class="drawer-item-tab section">
								<div class="action_buttons">
									<span class="jmapp_button jmapp_delete_button" @click="removeProvider(item, tab_index)">
										<i class="material-icons">delete</i>
									</span>
									<span class="jmapp_button" style="width:20px;">&nbsp;</span>
									<span class="jmapp_button jmapp_move" @click="moveUp(item.tabs, tab_index)">
										<i v-if="tab_index > 0"  class="material-icons">arrow_upward</i>
									</span>
									<span class="jmapp_button jmapp_move" @click="moveDown(item.tabs, tab_index)">
										<i v-if="tab_index < item.tabs.length"  class="material-icons">arrow_downward</i>
									</span>
									<span class="jmapp_button jmapp_collapse_button" @click="toggleHidden(tab)">
										<i v-if="tab.hidden"  class="material-icons">expand_more</i>
										<i v-if="!tab.hidden" class="material-icons">expand_less</i>
									</span>
								</div>
								<h5>{{item.title}} Tab #{{tab_index+1}} ({{tab.provider | niceProvider}})</h5>
								<div class="collapsible clear" :class="{collapsed:tab.hidden}">
									<div v-if="tab.provider != 'link'" class="input-row">
										<label for="">Provider Display Title: </label>
										<input type="text" v-model="tab.title">
									</div>
									
									<!-- PROVIDER SETTINGS -->
									<h5>{{tab.provider | niceProvider}} Arguments</h5>
									<small>{{tab.provider | providerInstructions}}</small>
									<div v-for="(val, key) in tab.arguments" class="">
										<div class="input-row">
											<label for="">{{key}}</label>
											<select
												v-if="providers[tab.provider].field_options && providers[tab.provider].field_options[key]"
												v-model="tab.arguments[key]">
												<option value="">Please Select One:</option>
												<option
													v-for="(optval,optkey) in providers[tab.provider].field_options[key]"
													:value="optkey">{{optval}}</option>
											</select>
											<input v-else type="text" v-model="tab.arguments[key]">
											<br />
											<small v-if="providers[tab.provider].field_help && providers[tab.provider].field_help[key]">
												{{providers[tab.provider].field_help[key]}}
											</small>
										</div>
									</div>
								</div>
							</div>
						
							<div v-if="item.tabs.length == 0 || providers[item.tabs[0].provider].tabbable">
								<h4>Add New Provider Tab to {{item.title}}</h4>
								<div class="input-row">
									<label for="">Provider Type: </label><br />
									<select v-model="selected_provider" @change="addProvider(item)">
										<option disabled value="">Please Select a Provider</option>
										<option v-for="provider in providers" v-if="provider.tabbable || item.tabs.length == 0" :value="provider.type">{{provider.display}}</option>
									</select>
								</div>
							</div>
						</div>
					</div>
					<div v-if="section.selected">
						<div class="input-row">
							<button class="button-primary" @click="addItem(section_index)">Add New Menu Item to {{section.title}}</button>
						</div>
					</div>
				</div>
				<div>
					<div class="input-row">
						<button class="button-primary" @click="addSection()">Add New Menu Section</button>
					</div>
				</div>
				<!-- <div>
					<h4>Save Data</h4>
					<div class="input-row">
						<button class="button-primary" @click="save()">Save App Data</button>
						<button class="button-primary" @click="refresh()">Refresh App Data</button>
					</div>
				</div> -->
			</div>
			
			<h3>Home Sections</h3>
	
			<div id="home-menu">
				<div v-for="(item, item_index) in app_config.home_menu.items" class="home-menu-item"
					:class="{selected:item.selected}">
					<div class="action_buttons">
						<span class="jmapp_button jmapp_delete_button" @click="removeHomeItem(item_index)">
							<i class="material-icons">delete</i>
						</span>
						<span class="jmapp_button" style="width:20px;">&nbsp;</span>
						<span class="jmapp_button jmapp_move" @click="moveUp(app_config.home_menu.items, item_index)">
							<i v-if="(item_index > 0)"  class="material-icons">arrow_upward</i>
						</span>
						<span class="jmapp_button jmapp_move" @click="moveDown(app_config.home_menu.items, item_index)">
							<i v-if="(item_index < app_config.home_menu.items.length)"  class="material-icons">arrow_downward</i>
						</span>
						
					</div>
					
					<h4 class="selectable"
						@click="select(item)">Home Page &gt; {{item.title}}</h4>
					
					<div v-if="item.selected">
						<div class="input-row">
							<label for="">Item Title: </label>
							<input type="text" v-model="item.title">
						</div>
						<div class="input-row">
							<label for="">
								Image URL
							</label>
							<input id="" type="text" v-model="item.image"><br />
						</div>
						<div v-show="item.image" id="" class="input-row">
							<img :src="item.image" alt="">
						</div>
					
						<h4>Home Page &gt; {{item.title}} • Provider(s)</h4>
						<small v-if="item.tabs.length > 0 && providers[item.tabs[0].provider].tabbable">This Provider can contain multiple tabs. Additional providers will display as additional tabs.</small>
						<div v-for="(tab, tab_index) in item.tabs" class="drawer-item-tab section">
							<div class="action_buttons">
								<span class="jmapp_button jmapp_delete_button" @click="removeProvider(item, tab_index)">
									<i class="material-icons">delete</i>
								</span>
								<span class="jmapp_button" style="width:20px;">&nbsp;</span>
							</div>
							<h5>{{item.title}} Tab #{{tab_index+1}} ({{tab.provider | niceProvider}})</h5>
							<div v-show="tab.provider != 'link'" class="input-row">
								<label for="">Provider Display Title: </label>
								<input type="text" v-model="tab.title">
							</div>
						
							<h5>{{tab.provider | niceProvider}} Arguments</h5>
							<small>{{tab.provider | providerInstructions}}</small>
							<div v-for="(val, key) in tab.arguments" class="">
								<div class="input-row">
									<label for="">{{key}}</label>
									<select
										v-if="providers[tab.provider].field_options && providers[tab.provider].field_options[key]"
										v-model="tab.arguments[key]">
										<option value="">Please Select One:</option>
										<option
											v-for="(optval,optkey) in providers[tab.provider].field_options[key]"
											:value="optkey">{{optval}}</option>
									</select>
									<input v-else type="text" v-model="tab.arguments[key]">
								</div>
							</div>
						</div>
					
						<div v-if="item.tabs.length == 0 || providers[item.tabs[0].provider].tabbable">
							<h4>Add New Provider Tab to {{item.title}}</h4>
							<div class="input-row">
								<label for="">Provider Type: </label><br />
								<select v-model="selected_provider" @change="addProvider(item)">
									<option disabled value="">Please Select a Provider</option>
									<option v-for="provider in providers" v-if="provider.tabbable || item.tabs.length == 0" :value="provider.type">{{provider.display}}</option>
								</select>
							</div>
						</div>
					</div>
				</div>
				<div>
					<div class="input-row">
						<button class="button-primary" @click="addHomeItem()">Add New Home Item</button>
					</div>
				</div>
			</div>
		</div>
		<div>
			<h3>Save Data</h3>
			<div class="input-row">
				<button class="button-primary" @click="save()">Save App Data</button>
				<button class="button-primary" @click="refresh()">Refresh App Data</button>
			</div>
			<small>{{response_message}}</small>
		</div>
	</div>
	<!-- end jmapp-app -->

</div>



<script>
	// axios.defaults.headers.post['Content-Type'] = 'application/x-www-form-urlencoded';

	// VUE app code goes here
	
	var providers = <?php echo json_encode(jmapp_get_providers()); ?>;
	
	var app_config = <?php echo json_encode(jmapp_read_menu_file()); ?>;
	
	var app = new Vue({
		el: '#jmapp-app',
		data: {
			providers: providers,
			app_config: app_config,
			selected_provider: '',
			response_message: '',
		},
		methods: {
			save: function(){
				Vue.set(this, 'response_message','saving...')
				var that = this;
				jQuery.ajax({
					url: ajaxurl,
					method:'POST',
					data: {'action': 'jmapp_save_menu', 'menu_data': JSON.stringify(this.cleanCopy(app_config))},
					success: function(data){
						console.log(data);
						if (data.message) that.response_message = data.message;
					}
				});
			},
			removeSection: function(section_index) {
				console.log(section_index)
				Vue.delete(this.app_config.drawer_menu.sections,section_index);
			},
			select: function(obj) {
				// find the object (is it an item or a section)
				// remove the selected value for everything else
				var foundIt = false;
				for (var i=0; i<app_config.drawer_menu.sections.length; i++){
					var section = app_config.drawer_menu.sections[i];

					// scan all items for this one
					for (var j=0; j<section.items.length; j++) {
						var item = section.items[j];
						if (item == obj) {
							foundIt = true;
							if (item.selected) {
								Vue.set(item, 'selected', false);
							}
							else {
								// select both the item and it's section
								Vue.set(item, 'selected', true);
								Vue.set(section, 'selected', true);
							}
						}
						else {
							Vue.set(item, 'selected', false);
						}
					}
					
					// if an item wasn't found, then we consider this section
					if (!foundIt) {
						if (section == obj) {
							foundIt = true;
							Vue.set(section, 'selected', !section.selected)
						}
						else {
							Vue.set(section, 'selected', false);
						}
					}
				}
				
				// if we haven't found it yet, we might be dealing with a home item
				// and not a drawer section.
				if (!foundIt) {
					for (var i=0; i<app_config.home_menu.items.length; i++){
						var item = app_config.home_menu.items[i];
						if (item == obj)
							Vue.set(item, 'selected',!item.selected);
						else
							Vue.set(item, 'selected',false);
					}
				}
			},
			addSection: function() {
				this.app_config.drawer_menu.sections.push({title:'',items:[], selected:true})
			},
			removeItem: function(section, item_index){
				Vue.delete(section.items, item_index)
			},
			addItem: function(section_index) {
				app_config.drawer_menu.sections[section_index].items.push({
					title:'Title',
					tabs:[],
					icon:'label',
					selected:true,
				});
				// newItems.push();
				// Vue.set(app_config.drawer_menu.sections[section_index], 'items', newItems);
			},
			addHomeItem: function() {
				this.app_config.home_menu.items.push({title:'', image:'', tabs:[], selected:true})
			},
			removeHomeItem: function(item_index){
				Vue.delete(app_config.home_menu.items, item_index);
			},
			moveUp: function(list, item_index){
				var newIndex = item_index - 1;
				if (newIndex < 0) return;
				var item = list.splice(item_index,1)[0];
				list.splice(newIndex, 0, item);
			},
			moveDown: function(list, item_index){
				var newIndex = item_index + 1;
				if (newIndex >= list.length) return;
				var item = list.splice(item_index,1)[0];
				list.splice(newIndex, 0, item);
			},
			toggleHidden: function(item) {
				console.log(item);
				var newValue = (item.hidden) ? false : true;
				Vue.set(item, 'hidden', newValue);
			},
			removeProvider: function(item, tab_index) {
				console.log(item);
				console.log(tab_index);
				Vue.delete(item.tabs, tab_index);
			},
			addProvider: function(item) {
				console.log(item);
				
				// prepare data for the new provider we are adding
				var provider_name = this.selected_provider;
				var provider_title = '';
				var provider_arguments = {}
				for (var i=0;i<this.providers[provider_name].arguments.length;i++) {
					var key = this.providers[provider_name].arguments[i]
					provider_arguments[key] = '';
				}
				
				if (!item.tabs) Vue.set(item, 'tabs', []);
				
				// if there is already a provider, convert to tabs
				if (item.provider && item.provider != '') {
					var tmptab = {}
					tmptab.provider  = item.provider;
					tmptab.title     = item.title;
					tmptab.arguments = item.arguments;
					
					Vue.delete(item,'provider');
					Vue.delete(item,'arguments');
					
					// tmptab should already be reactive
					item.tabs.push(tmptab)
				}
				
				// if there are tabs add new provider to the tabs and return
				if (item.tabs) {
					var newTab = {
						provider:  provider_name,
						title:     provider_title,
						arguments: provider_arguments
					}
					item.tabs.push(newTab)
				}
				this.selected_provider = '';
			},
			handleData: function(data){
				// clear out the app_config
				Vue.set(app_config,data);
			},
			prepareData: function() {
			},
			cleanCopy: function(obj) {
				var keys_to_remove = ['selected', 'hidden'];
				if (typeof(obj) == 'object') {
					if (Array.isArray(obj)) {
						var retval = [];
						for (var i = 0; i<obj.length; i++) {
							var tmpObj = obj[i];
							retval.push(this.cleanCopy(tmpObj));
						}
						return retval;
					}
					else {
						var retval = {}
						var keys = Object.keys(obj);
						for (var i=0;i<keys.length;i++) {
							var key = keys[i];
							if (keys_to_remove.indexOf(key) == -1) retval[key] = this.cleanCopy(obj[key]);
						}
						return retval;
					}
				}
				else {
					return obj;
				}
			},
			refresh: function() {
				Vue.set(this, 'response_message','loading...')
				var that = this;
				jQuery.ajax({
					url: ajaxurl,
					method:'POST',
					data: {'action': 'jmapp_get_menu'},
					success: function(data){
						console.log(data);
						if (data.message) that.response_message = data.message;
						app.handleData(data.data);
					}
				});
				// axios.post(ajaxurl, {
				// 	action: 'jmapp_get_menu',
				// })
				// .then(function(response){
				// 	console.log(response);
				// })
			}
		},
		filters: {
			niceProvider: function(provider_type){
				if (this.providers[provider_type]) return this.providers[provider_type].display;
				else return provider_type;
			},
			providerInstructions: function(provider_type){
				if (this.providers[provider_type]) return this.providers[provider_type].instructions;
				else return '';
			}
		},
		mounted: function() {},
		
	})
	
</script>