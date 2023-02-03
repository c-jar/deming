@extends("layout")

@section("content")
<div class="p-3">
    <div data-role="panel" data-title-caption="{{ trans('cruds.measure.index') }}" data-collapsible="true" data-title-icon="<span class='mif-chart-line'></span>">

			<div class="grid">
				<div class="row">
					<div class="cell-1">
			    		<strong>{{ trans('cruds.measure.title') }}</strong>
			    	</div>
					<div class="cell-4">
						<select id='domain_id' name="domain_id" size="1" width='10'>
						    <option value="0">-- {{ trans('cruds.domain.choose') }} --</option>
							@foreach ($domains as $domain)
						    	<option value="{{ $domain->id }}"
									@if (((int)Session::get("domain"))==$domain->id)		
										selected 
									@endif >
						    		{{ $domain->title }} - {{ $domain->description }}
						    	</option>
						    @endforeach
						</select>
					</div>
					<div class="cell-7" align="right">
						<button class="button primary" onclick="location.href = '/measures/create';">
			            <span class="mif-plus"></span>
			            &nbsp;
						{{ trans('common.new') }}
					</button>
					</div>
				</div>

			<script>
				window.addEventListener('load', function(){
			    var select = document.getElementById('domain_id');

			    select.addEventListener('change', function(){
			        window.location = '/measures?domain=' + this.value;
			    }, false);
			}, false);
			</script>

				<div class="row">
					<div class="cell">

			<table class="table striped row-hover cell-border"
		       data-role="table"
		       data-rows="10"
		       data-show-activity="true"
		       data-rownum="false"
		       data-check="false"
		       data-check-style="1">
			   <thead>
				    <tr>
						<th class="sortable-column sort-asc" width="10%">{{ trans('cruds.measure.fields.domain') }}</th>
						<th class="sortable-column sort-asc" width="10%">{{ trans('cruds.measure.fields.clause') }}</th>
						<th class="sortable-column sort-asc" width="70%">{{ trans('cruds.measure.fields.name') }}</th>
						<th width="10% class="sortable-column sort-asc" width="70%">{{ trans('cruds.control.fields.plan_date') }}</th>
				    </tr>
			    </thead>
			    <tbody>
			@foreach($measures as $measure)
				<tr>
					<td>
						<a href="/domains/{{$measure->domain_id}}">
							{{ $measure->domain->title }}
						</a>
					</td>
					<td><a href="/measures/{{ $measure->id}}">
						@if (strlen($measure->clause)==0)
							None
						@else
							{{ $measure->clause }}
						@endif
						</a>
					</td>
					<td>{{ $measure->name }}</td>
					<td>
						<a href="/measure/plan/{{ $measure->id }}">{{ $measure->planDate() }}</a>
					</td>
				</tr>
			@endforeach
			</tbody>
			</table>
		</div>
	</div>
</div>
@endsection
