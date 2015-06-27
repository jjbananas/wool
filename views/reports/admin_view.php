<div class="pad">
	<ol class="breadcrumbs">
		<li><?php echo linkTo("Home", array("action"=>"index")) ?></li>
		<li><?php echo linkTo("Reports", array("action"=>"index")) ?></li>
		<li>Payment Report</li>
	</ol>
</div>

<div class="pad">
	<h1>Payment Report</h1>
	<p>Payments and other transactions taken against your payment gateway.</p>
</div>

<div class="pad">
	<div class="pod">
		<table class="dataGrid">
			<thead>
				<tr>
					<th>Payment ID#</th>
					<th>Order ID#</th>
					<th>Date</th>
					<th>Customer</th>
					<th>Invoice Amount</th>
					<th>Payment Taken</th>
					<th>Difference</th>
				</tr>
			</thead>

			<tbody>
				<tr>
					<td>55</td>
					<td><a href="">84</a></td>
					<td>Mon 5th Mar 2011</td>
					<td><a href="">Tony Marklove</a></td>
					<td>100.00</td>
					<td>100.00</td>
					<td>0.00</td>
				</tr>
				<tr>
					<td>57</td>
					<td><a href="">53</a></td>
					<td>Tue 6th Mar 2011</td>
					<td><a href="">Boris Greshenko</a></td>
					<td>100.00</td>
					<td>90.00</td>
					<td>-10.00</td>
				</tr>
			</tbody>

			<tfoot>
				<tr class="totals">
					<td></td>
					<td></td>
					<td></td>
					<td></td>
					<td></td>
					<td></td>
					<td>1641</td>
				</tr>
			</tfoot>
		</table>
	</div>
</div>

<div class="pad">
	<h2>Tax Free Payments Taken</h2>
	<p class="desc">Summary of non-taxed payments</p>

	<div class="pod">
		<table class="dataGrid">
			<thead>
				<tr>
					<th>Payment ID#</th>
					<th>Order ID#</th>
					<th>Date</th>
					<th>Customer</th>
					<th>Invoice Amount</th>
					<th>Payment Taken</th>
					<th>Difference</th>
				</tr>
			</thead>

			<tbody>
				<tr>
					<td>55</td>
					<td><a href="">84</a></td>
					<td>Mon 5th Mar 2011</td>
					<td><a href="">Tony Marklove</a></td>
					<td>100.00</td>
					<td>100.00</td>
					<td>0.00</td>
				</tr>
				<tr>
					<td>57</td>
					<td><a href="">53</a></td>
					<td>Tue 6th Mar 2011</td>
					<td><a href="">Boris Greshenko</a></td>
					<td>100.00</td>
					<td>90.00</td>
					<td>-10.00</td>
				</tr>
			</tbody>

			<tfoot>
				<tr class="totals">
					<td></td>
					<td></td>
					<td></td>
					<td></td>
					<td></td>
					<td></td>
					<td>1641</td>
				</tr>
			</tfoot>
		</table>
	</div>
</div>

<div class="pad">
	<h2>Breakdown of Payments by Tax</h2>
	<p class="desc">Summary of non-taxed payments</p>

	<div class="pod">
		<table class="dataGrid">
			<thead>
				<tr>
					<th>Payment ID#</th>
					<th>Order ID#</th>
					<th>Date</th>
					<th>Customer</th>
					<th>Invoice Amount</th>
					<th>Payment Taken</th>
					<th>Difference</th>
				</tr>
			</thead>

			<tbody>
				<tr>
					<td>55</td>
					<td><a href="">84</a></td>
					<td>Mon 5th Mar 2011</td>
					<td><a href="">Tony Marklove</a></td>
					<td>100.00</td>
					<td>100.00</td>
					<td>0.00</td>
				</tr>
				<tr>
					<td>57</td>
					<td><a href="">53</a></td>
					<td>Tue 6th Mar 2011</td>
					<td><a href="">Boris Greshenko</a></td>
					<td>100.00</td>
					<td>90.00</td>
					<td>-10.00</td>
				</tr>
			</tbody>

			<tfoot>
				<tr class="totals">
					<td></td>
					<td></td>
					<td></td>
					<td></td>
					<td></td>
					<td></td>
					<td>1641</td>
				</tr>
			</tfoot>
		</table>
	</div>
</div>

<div class="pad">
	<h2>Reserved Payments</h2>
	<p class="desc">Reserved payments have not been captured and will not show up in your invoices. This is usually due to the fact that the orders have not yet been dispatched.</p>

	<div class="pod">
		<table class="dataGrid">
			<thead>
				<tr>
					<th>Transaction Type</th>
					<th>Status/Reason</th>
					<th># Occurances</th>
				</tr>
			</thead>

			<tbody>
				<tr>
					<td>payment</td>
					<td>invalid</td>
					<td>11</td>
				</tr>
				<tr>
					<td>reserve</td>
					<td>failed</td>
					<td>11</td>
				</tr>
			</tbody>

			<tfoot>
				<tr class="totals">
					<td></td>
					<td></td>
					<td>22</td>
				</tr>
			</tfoot>
		</table>
	</div>
</div>
