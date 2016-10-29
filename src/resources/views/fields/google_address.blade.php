<?php
    $entity_model = $crud->getModel();
 	
 	// For update form, get initial state of the entity
    if( isset($id) && $id ){
    	$entity_column = $entity_model::find($id)->getAttributes();
	}

	// Get the Provider
	$provider = isset($field['provider']) ? $field['provider'] : 'algolia';

	// Get the API Key
	$googleApiKey = isset( $field['google_api_key'] ) ? $field['google_api_key']  : ( config('backpack.google_api_key', env('GOOGLE_API_KEY', null)));
	
	// Translate the backpack address components name in the chosen provider components name
	if (isset($field['components'])){
		$provider_fields = config('backpack.address.provider.' . $provider);
		foreach ($field['components'] as $key => $value){
			if ($key != $provider_fields[$key]){
				$field['components'][$provider_fields[$key]] = $field['components'][$key];
				unset($field['components'][$key]);
			}
		}
	}
		
	// Initialize the error messages
	$notification = new stdClass();
	$notification->title = trans('backpack::crud.address_google_error_title');
	$notification->message = trans('backpack::crud.address_google_error_message');

?>

<div @include('crud::inc.field_wrapper_attributes')>
    <label>{!! $field['label'] !!}</label>       
    <input 
      	type="text" 
      	name="{{ $field['name'] }}" 
      	id="{{ $field['name'] }}"
      	value="{{ old($field['name'], isset($entity_column[$field['name']]) ? $entity_column[$field['name']] : null) }}"
      	@include('crud::inc.field_attributes')   
    >
   
    {{-- HINT --}}
    @if (isset($field['hint']))
        <p class="help-block">{!! $field['hint'] !!}</p>
    @endif
</div>

@if(isset($field['components'])) 
	@foreach ($field['components'] as $attribute)
		<div @include('crud::inc.field_wrapper_attributes') >
		    <label>{!! $attribute['label'] !!}</label>
		    <input 
		      	type="text" 
		      	name="{{ $attribute['name'] }}"
		      	id="{{ $attribute['name'] }}"
		      	value="{{ old($attribute['name'], isset($entity_column[$attribute['name']]) ? $entity_column[$attribute['name']] : null) }}"
		      	readonly
		      	@include('crud::inc.field_attributes')
		    >
		</div>
	@endforeach
@endif

{{-- Note: you can use  to only load some CSS/JS once, even though there are multiple instances of it --}}

{{-- ########################################## --}}
{{-- Extra CSS and JS for this particular field --}}
{{-- If a field type is shown multiple times on a form, the CSS and JS will only be loaded once --}}
@if ($crud->checkIfFieldIsFirstOfItsType($field, $fields))

    {{-- FIELD CSS - will be loaded in the after_styles section --}}
    
    {{-- @push('crud_fields_styles')
        <!-- no styles -->
    @endpush --}}

    {{-- FIELD JS - will be loaded in the after_scripts section --}}
    @push('crud_fields_scripts')
        <script>
    
			var field = {!! json_encode($field) !!}  
			var notification = {!! json_encode($notification) !!}

		</script>
		@if($provider == 'google')
			<script>
				function initAutocomplete() {
	  
				 	if(document.getElementById(field.name)){
				    	var autocomplete = new google.maps.places.Autocomplete((document.getElementById(field.name)),{types: ['address']});
				    	autocomplete.addListener('place_changed', function(){fillInAddress(autocomplete)});
				  	}
				}

				function fillInAddress(autocomplete) {
				  	// Get the place details from the autocomplete object.
				 	var place = autocomplete.getPlace();
				   	var val = [];

				   	document.getElementById(field.name).value = place.formatted_address;

				   	if (place.address_components){ // Google API provides the components for the address
				  	
					  	// Get each component of the address from the place details
					  	for (var i = 0; i < place.address_components.length; i++) {
					    	var addressType = place.address_components[i].types[0];
					    	val[addressType] = place.address_components[i];
					  	}
						
						// Fill the corresponding field on the form if it exists.
					  	for (var component in field.components) {
					    	document.getElementById(field.components[component].name).readOnly = false;
					    	if (val[component]){
					    		document.getElementById(field.components[component].name).value = typeof val[component][field.components[component].type] !== 'undefined' ? val[component][field.components[component].type] : val[component]['long_name'];	
					    	} else {
					    		document.getElementById(field.components[component].name).value = '';
					    	}
					  	}

					} else { // Google API doesn't provide the components for the address
						
						for (var component in field.components) {
							document.getElementById(field.components[component].name).value = '';
					    	document.getElementById(field.components[component].name).readOnly = false;
					    }

					    $(function(){
					        new PNotify({
					            title: notification['title'],
					            text: notification['message'],
					            icon: false,
					        });
					    });	
					}
				}

	        </script>
	        <script src="https://maps.googleapis.com/maps/api/js?key={{ $googleApiKey }}&amp;libraries=places&amp;callback=initAutocomplete" async defer></script>
	    @else
		    <script src="https://cdn.jsdelivr.net/places.js/1/places.min.js"></script>
			<script>
				(function() {
					var placesAutocomplete = places({
						container: document.querySelector('#address'),
						type: 'address',
					});
					placesAutocomplete.on('change', function resultSelected(e) {

						document.getElementById(field.name).value = e.suggestion.value;

						// Insert country and countryCode in the same array for provider compatibility
						e.suggestion.hit['country'] = [e.suggestion.country,e.suggestion.countryCode.toUpperCase()];
					    
					    // Fill the corresponding field on the form if it exists.
					    for (var component in field.components) {
					    	document.getElementById(field.components[component].name).readOnly = false;
					    	if (Array.isArray(e.suggestion.hit[component])){
					    		if (e.suggestion.hit[component].length > 1 && field.components[component].type == 'short_name'){
					    			document.getElementById(field.components[component].name).value = short(e.suggestion.hit[component]) || '';
					    		} else {
					    			document.getElementById(field.components[component].name).value = long(e.suggestion.hit[component]) || '';	
					    		}
					    	} else {
					    		document.getElementById(field.components[component].name).value = e.suggestion.hit[component] || '';
					    	}
					    }

					});
				})();

				function long(a) {
					var l = 0;
					for (var i = 0; i < a.length; i++) {
						if (a[l].length < a[i].length) l = i;
					}
					return a[l];
				}

				function short(a) {
					var l = 0;
					for (var i = 0; i < a.length; i++) {
						if (a[l].length > a[i].length) l = i;
					}
					return a[l];
				}
    </script>
			</script>
	    @endif    
    @endpush

@endif
{{-- End of Extra CSS and JS --}}
{{-- ########################################## --}}
