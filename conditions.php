<?php

include_once 'db.php';
session_start();
date_default_timezone_set('Asia/Kolkata');
/*if(!isset($_SESSION['id'])){echo '<script>window.location.href="index.php"</script>';}*//*To redirect to login page when not logged in*/

/*Signup*/
if(isset($_POST['signup'])){
	$username = mysqli_real_escape_string($conn,$_POST['username']);
	$password = mysqli_real_escape_string($conn,$_POST['password']);
	$email = mysqli_real_escape_string($conn,$_POST['email']);
	$fullName = mysqli_real_escape_string($conn,$_POST['fullName']);
	/*Check if user already created*/
	$checkUser = mysqli_query($conn,"SELECT * FROM users WHERE username = '$username' OR email = '$email'");
	if(mysqli_num_rows($checkUser)>0){
		echo '<span class="errorMessage">username or e-mail already available! Try with a new one / Login</span>';
	}else{
		$name = ucwords($fullName);/*To convert name to first letter Capital Name*/
		$addUser = mysqli_query($conn,"INSERT INTO users (username, password, email, name) VALUES ('$username', '$password', '$email', '$name')");
		echo '<span class="successMessage">User added successfully! Login to continue..</span>';
	}
}

/*Login*/
if(isset($_POST['login'])){
	$username = mysqli_real_escape_string($conn,$_POST['username']);
	$password = mysqli_real_escape_string($conn,$_POST['password']);
	/*Check for user*/
	$checkUser = mysqli_query($conn,"SELECT * FROM users WHERE (username = '$username' OR email = '$username')");
	if(mysqli_num_rows($checkUser)>0){
		while($rowUser = mysqli_fetch_assoc($checkUser)){
			$checkPassword = $rowUser['password'];
			$passwordChecked="notVerified";
			$encID=(149118912*$rowUser['id'])+149118912;
			if(isset($_COOKIE['userID'])){
				if($_COOKIE['userID']==$encID){$passwordChecked="verified";}
				else if($checkPassword == $password){$passwordChecked="verified";}
				else{$passwordChecked="notVerified";}
			}else{
				if($checkPassword == $password){$passwordChecked="verified";}
				else{$passwordChecked="notVerified";}
			}
			if($passwordChecked=="verified"){
				$_SESSION['id'] = $rowUser['id'];
				$_SESSION['username'] = $rowUser['username'];
				if($_POST['autoLogin']=='enabled'){
					setcookie('autoLogin','yes',time()+180000,'/');
					setcookie('username',$rowUser['username'],time()+180000,'/');
					$encID=(149118912*$rowUser['id'])+149118912;
					setcookie('userID',$encID,time()+180000,'/');
				}
				echo 'loginSuccess';
				exit();
			}else{
				echo '<span class="errorMessage">Invalid Credentials</span>';
			}
		}
	}else{
		echo '<span class="errorMessage">No User Found! Signup to create an account</span>';
	}
}

/*Add Expense*/
if(isset($_POST['addExpense'])){
	$amount = mysqli_real_escape_string($conn,$_POST['expenseAmount']);
	$date = mysqli_real_escape_string($conn,$_POST['expenseDate']);
	$category = mysqli_real_escape_string($conn,$_POST['expenseCategory']);
	$details = mysqli_real_escape_string($conn,$_POST['expenseDetails']);
	$username = mysqli_real_escape_string($conn,$_POST['expenseUsername']);
	$Budget = mysqli_real_escape_string($conn,$_POST['expenseBudget']);
	$type = $_POST['type'];
	$checkProcess='check';
	if($Budget!='noBudgetRegd' && $Budget!='cash'){
		/*Change Budget Data*/
		$BudgetCheck=mysqli_query($conn,"SELECT * FROM Budget WHERE BudgetUsername='$username' AND BudgetName='$Budget'");
		if(mysqli_num_rows($BudgetCheck)>0){
			while($rowBudgets=mysqli_fetch_assoc($BudgetCheck)){
				$currentBudgetValue=$rowBudgets['BudgetValue'];
			}
			if($type=='expense'){$newBudgetValue=$currentBudgetValue-$amount;}
			elseif($type=='income'){$newBudgetValue=$currentBudgetValue+$amount;}
			if($newBudgetValue<0){
				echo 'noEnoughMoneyInBudget';
				$checkProcess='stop';
			}else{
				echo 'enoughMoneyInBudget';
				$checkProcess='continue';
			}
		}
	}else{
		echo 'noBudgetRegd';
	}

	if($checkProcess!='stop'){
		$addExpense = mysqli_query($conn,"INSERT INTO expenses (username, type, amount, date, category, Budget, details) VALUES ('$username', '$type', '$amount', '$date', '$category', '$Budget', '$details')");
		$addBudgetHistory = mysqli_query($conn, "INSERT INTO Budgethistory (BudgetUsername, BudgetNameFrom, BudgetNameTo, BudgetValue, BudgetTransferDate, type, category, details) VALUES ('$username', '$Budget', 'BudgetExpenseOK', '$amount', '$date', '$type', '$category', '$details')");
		echo '-period-<span class="successMessage">'.ucwords($type).' Added</span>
		-period-';/*To send two inputs to client*/
	 	$totalExpense = getNewExpenseDetails($conn, $username, 'expense'); /*Get New Expense*/
	 	$totalIncome = getNewExpenseDetails($conn, $username, 'income'); /*Get New Income*/
		$totalBudget = $totalIncome - $totalExpense;
		echo number_format($totalExpense);
		echo '-period-';
		echo number_format($totalIncome);
		echo '-period-';
		echo number_format($totalBudget);
		/*Get Marquee Outputs*/

		/*Calculate Today Expenses*/
		$todayDate = date('Y-m-d',time());
		$yesterdayDate = date('Y-m-d', strtotime("-1 days"));
		$thisMonth = date('m',time());
		$thisYear = date('Y',time());
		$totalTodayExpenses = 0; $totalTodayIncome = 0;
		$totalYesterdayExpenses = 0; $totalYesterdayIncome = 0;
		$totalThisMonthExpenses = 0; $totalThisMonthIncome = 0;
		$getTodayExpense = mysqli_query($conn,"SELECT SUM(amount) AS todayExpense FROM expenses WHERE type = 'expense' AND category != 'BudgetTransfer' AND username = '$username' AND date = '$todayDate' ORDER BY id DESC");
		$getTodayExpenseCount = mysqli_query($conn,"SELECT * FROM expenses WHERE type = 'expense' AND category != 'BudgetTransfer' AND username = '$username' AND date = '$todayDate' ORDER BY id DESC");
		if(mysqli_num_rows($getTodayExpenseCount)>0){while($rowTodayExpense = mysqli_fetch_assoc($getTodayExpense)){$totalTodayExpenses = $rowTodayExpense['todayExpense'];}}

		/*Calculate Today Incomes*/
		$getTodayIncome = mysqli_query($conn,"SELECT SUM(amount) AS todayIncome FROM expenses WHERE type = 'income' AND category != 'BudgetTransfer' AND username = '$username' AND date = '$todayDate' ORDER BY id DESC");
		$getTodayIncomeCount = mysqli_query($conn,"SELECT * FROM expenses WHERE type = 'income' AND category != 'BudgetTransfer' AND username = '$username' AND date = '$todayDate' ORDER BY id DESC");
		if(mysqli_num_rows($getTodayIncomeCount)>0){while($rowTodayIncome = mysqli_fetch_assoc($getTodayIncome)){$totalTodayIncome = $rowTodayIncome['todayIncome'];}}

		/*Calculate Yesterday Expenses*/
		$getYesterdayExpense = mysqli_query($conn,"SELECT SUM(amount) AS yesterdayExpense FROM expenses WHERE type = 'expense' AND category != 'BudgetTransfer' AND username = '$username' AND date = '$yesterdayDate' ORDER BY id DESC");
		$getYesterdayExpenseCount = mysqli_query($conn,"SELECT * FROM expenses WHERE type = 'expense' AND category != 'BudgetTransfer' AND username = '$username' AND date = '$yesterdayDate' ORDER BY id DESC");
		if(mysqli_num_rows($getYesterdayExpenseCount)>0){while($rowYesterdayExpense = mysqli_fetch_assoc($getYesterdayExpense)){$totalYesterdayExpenses = $rowYesterdayExpense['yesterdayExpense'];}}

		/*Calculate Yesterday Incomes*/
		$getYesterdayIncome = mysqli_query($conn,"SELECT SUM(amount) AS yesterdayIncome FROM expenses WHERE type = 'income' AND category != 'BudgetTransfer' AND username = '$username' AND date = '$yesterdayDate' ORDER BY id DESC");
		$getYesterdayIncomeCount = mysqli_query($conn,"SELECT * FROM expenses WHERE type = 'income' AND category != 'BudgetTransfer' AND username = '$username' AND date = '$yesterdayDate' ORDER BY id DESC");
		if(mysqli_num_rows($getYesterdayIncomeCount)>0){while($rowYesterdayIncome = mysqli_fetch_assoc($getYesterdayIncome)){$totalYesterdayIncome = $rowYesterdayIncome['yesterdayIncome'];}}

		/*Calculate Month's Expenses*/
		$getThisMonthExpense = mysqli_query($conn,"SELECT SUM(amount) AS thisMonthExpense FROM expenses WHERE type = 'expense' AND category != 'BudgetTransfer' AND username = '$username' AND MONTH(date) = '$thisMonth' AND YEAR(date) = '$thisYear' ORDER BY id DESC");
		$getThisMonthExpenseCount = mysqli_query($conn,"SELECT * FROM expenses WHERE type = 'expense' AND category != 'BudgetTransfer' AND username = '$username' AND MONTH(date) = '$thisMonth' AND YEAR(date) = '$thisYear' ORDER BY id DESC");
		if(mysqli_num_rows($getThisMonthExpenseCount)>0){while($rowThisMonthExpense = mysqli_fetch_assoc($getThisMonthExpense)){$totalThisMonthExpenses = $rowThisMonthExpense['thisMonthExpense'];}}

		/*Calculate Month's Incomes*/
		$getThisMonthIncome = mysqli_query($conn,"SELECT SUM(amount) AS thisMonthIncome FROM expenses WHERE type = 'income' AND category != 'BudgetTransfer' AND username = '$username' AND MONTH(date) = '$thisMonth' AND YEAR(date) = '$thisYear' ORDER BY id DESC");
		$getThisMonthIncomeCount = mysqli_query($conn,"SELECT * FROM expenses WHERE type = 'income' AND category != 'BudgetTransfer' AND username = '$username' AND MONTH(date) = '$thisMonth' AND YEAR(date) = '$thisYear' ORDER BY id DESC");
		if(mysqli_num_rows($getThisMonthIncomeCount)>0){while($rowThisMonthIncome = mysqli_fetch_assoc($getThisMonthIncome)){$totalThisMonthIncome = $rowThisMonthIncome['thisMonthIncome'];}}
		echo '-period-'.number_format($totalTodayExpenses).'-period-'.number_format($totalTodayIncome).
		'-period-'.number_format($totalYesterdayExpenses).'-period-'.number_format($totalYesterdayIncome).
		'-period-'.number_format($totalThisMonthExpenses).'-period-'.number_format($totalThisMonthIncome);

		/*Change Budget Data*/
		if($checkProcess=='continue'){
			$addBudgetMoney=mysqli_query($conn,"UPDATE Budget SET BudgetValue='$newBudgetValue' WHERE BudgetName='$Budget' AND BudgetUsername='$username'");
			echo'-period-';
			getBudgetSummary($conn,$username);
			echo'-period-';
			getBudgetHistory($conn,$username);
		}
	}else{
		echo '-period-noEnoughMoneyInBudgetAgain';
	}
}

/*Show Expenses*/
if(isset($_POST['showExpense'])){
	$changeType = $_POST['changeType'];
	$username = $_POST['username'];
	$getExpenses = mysqli_query($conn, "SELECT * FROM expenses WHERE username = '$username' AND type = '$changeType' ORDER BY date DESC, id DESC");
	if(mysqli_num_rows($getExpenses)>0){
		echo '<div id = "expensesListDiv">
			 <div class = "expensesListMainHeading">
				<div class = "listMainHeadingName">'.ucwords($changeType).' Details</div>
				<div id = "loaderOf'.$changeType.'"></div>
				<div class = "closeButton" onclick = "hideListDivs()">Close</div>
			</div>';
			/*Month-Wise Expese*/
			$getAllMonths = mysqli_query($conn, "SELECT DISTINCT month(date) AS Month, year(date) AS Year FROM expenses WHERE type = '$changeType' AND username = '$username' ORDER BY date DESC");
			if(mysqli_num_rows($getAllMonths)>0){
				echo '<div id = "expenseMonthList">';
					while ($rowMonths = mysqli_fetch_assoc($getAllMonths)) {
						$rowMonth = $rowMonths['Month'];
						$rowYear = $rowMonths['Year'];
						echo '<div class = "'.$changeType.'sList" onclick = "toggleSubListDiv(\''.$changeType.'\', \''.'Day'.'\', \''.$rowMonth.'\', \''.$rowYear.'\')">
							<div>';
								echo date('F Y', mktime(0, 0, 0, $rowMonth+1, 0, $rowYear)).' - ';
								/*mktime used to get time details mktime(hour, minute, second, month, date, year)*/
								/*Get the expense of each month*/
								$getMonthExpenses = mysqli_query($conn, "SELECT SUM(amount) AS monthExpense FROM expenses WHERE month(date) = $rowMonth AND year(date) = $rowYear AND type = '$changeType' AND username = '$username'");
								if(mysqli_num_rows($getMonthExpenses)>0){
									while($rowMonthExpense = mysqli_fetch_assoc($getMonthExpenses)){
										echo '&#8377;<span ';
											if($changeType == 'income'){
												echo ' class = "successMessage">';
											}else{
												echo ' class = "errorMessage">';
											}
											echo number_format($rowMonthExpense['monthExpense']).'</span><br>';
									}
								}
							echo '</div>
							<div id = "loader'.$changeType.'DayListOfMonth'.$rowMonth.'AndYear'.$rowYear.'"></div>
							<div class = "arrowDiv">
								<div class = "rightDoubleArrow"></div>
							</div>
						</div>';
						/*Day Wise Expenses*/
						echo '<div id = "'.$changeType.'DayListOfMonth'.$rowMonth.'AndYear'.$rowYear.'"></div>';
					}
				echo '</div>';
			}
		echo '</div>';
	}else{
		echo 'No expenses added!';
	}
}

/*Show Expenses Day-Wise Expese*/
if(isset($_POST['checkSubExpense'])){
	$type = $_POST['type'];
	$listType = $_POST['listType'];
	$month = $_POST['month'];
	$year = $_POST['year'];
	$username = getUsername($conn, $_SESSION['id']);

	$getAllDays = mysqli_query($conn, "SELECT * FROM expenses WHERE type = '$type' AND username = '$username' AND month(date) = '$month' AND year(date) = '$year' ORDER BY date DESC");
	if(mysqli_num_rows($getAllDays)>0){
		/*Dummy Date*/$checkDate = date('1757-08-15');
		while ($rowDays = mysqli_fetch_assoc($getAllDays)) {
			if($checkDate != $rowDays['date']){
				$rowDay = $rowDays['date'];
				$rowDayInNumberFormat = strtotime(date('Y-m-d 05:30:00', strtotime($rowDay)));/*Send date in number format*/
				echo '<div class = "expensesSubList" onclick = "toggleSubLowerListDiv(\''.$type.'\', \''.'All'.'\', \''.$rowDayInNumberFormat.'\')">
					<div>';
						/*mktime used to get time details mktime(hour, minute, second, month, date, year)*/
						echo '&nbsp;'.date('d F Y (D)', strtotime($rowDay)).' - ';
						/*Get the expense of each day*/
						$getDayExpenses = mysqli_query($conn, "SELECT SUM(amount) AS dayExpense FROM expenses WHERE date = '$rowDay' AND type = '$type' AND username = '$username' ORDER BY date DESC");
						if(mysqli_num_rows($getDayExpenses)>0){
							while($rowDayExpense = mysqli_fetch_assoc($getDayExpenses)){
								echo '&#8377;<span ';
								if($type == 'income'){
									echo ' class = "successMessage">';
								}else{
									echo ' class = "errorMessage">';
								}
								echo number_format($rowDayExpense['dayExpense']).'</span><br>';
							}
						}

						$checkDate = $rowDays['date'];
						$checkDateInNumberFormat = strtotime(date('Y-m-d 05:30:00', strtotime($checkDate)));

					echo '</div>
					<div id = "loader'.$type.'AllListOfDate'.$checkDateInNumberFormat.'"></div>
					<div class = "arrowDiv">
						<div class = "rightDoubleArrow"></div>
						<div class = "rightDoubleArrow"></div>
					</div>
				</div>';
				echo '<div id = "'.$type.'AllListOfDate'.$checkDateInNumberFormat.'"></div>';
			}
		}
	}
}

/*Show Expense Sub Inner Details*/
if(isset($_POST['checkSubInnerExpense'])){
	$type = $_POST['type'];
	$listType = $_POST['listType'];
	$dateInNumberFormat = $_POST['date'];
	$username = getUsername($conn, $_SESSION['id']);

	$checkDate = date('Y-m-d', $dateInNumberFormat);
	$getExpenses = mysqli_query($conn, "SELECT * FROM expenses WHERE date = '$checkDate' AND username = '$username' AND type = '$type' ORDER BY date DESC, id DESC");
	if(mysqli_num_rows($getExpenses)>0){
		echo '<div class="tableContainer"><table class="analysisTable">
			<thead>
				<tr>
					<th>Category</th><th>Money</th><th>Details</th><th>Budget</th><th>Edit</th><th>Delete</th>
				</tr>
			</thead>
			<tbody>
		';
		while($rowExpenses = mysqli_fetch_assoc($getExpenses)){
			/*<div class = "expensesSubLowerList">
				<div>
					<span>&nbsp;&nbsp;&nbsp;'.date('d-M-Y (D)', strtotime($rowExpenses['date'])).'</span>
					- <i>'.ucwords($rowExpenses['category']).'</i> - <b>&#8377;'.number_format($rowExpenses['amount']).'</b>
					<div>&nbsp;&nbsp;&nbsp;'.$rowExpenses['details'].'</div>
				</div>
				<div>
					<button class = "expensesEditButton" onclick="editExpenses('.$rowExpenses['id'].')">Edit</button>
					<button class = "expensesDeleteButton" onclick="deleteExpenses('.$rowExpenses['id'].')">Delete</button>
				</div>
			</div>
			<div>*/
			if(date('h',strtotime($rowExpenses['date']))==12 && date('i',strtotime($rowExpenses['date']))==0 && date('A',strtotime($rowExpenses['date']))=='AM'){
				$getDateTime='-';
			}else{
				$getDateTime=date('h:i A',strtotime($rowExpenses['date']));
			}

			echo '<tr>
				<td>'.ucwords($rowExpenses['category']).'</td><td>'.'&#8377;'.number_format($rowExpenses['amount']).'</td><td>';
				if($rowExpenses['details']!=''){echo $rowExpenses['details'];}else{echo '-';}
				echo'</td><td>';
				if($rowExpenses['Budget']!=''&&$rowExpenses['Budget']!='noBudgetRegd'){echo $rowExpenses['Budget'];}else{echo '-';}
				echo'</td><td><button class = "expensesEditButton" onclick="editExpenses('.$rowExpenses['id'].')">Edit</button></td>
				<td><button class = "expensesDeleteButton" onclick="deleteExpenses('.$rowExpenses['id'].')">Delete</button></td>
			</tr>';
		}
		echo'</tbody>
		</table></div>';
	}
}

/*Show Budget*/
if(isset($_POST['showBudget'])){
	$username = $_POST['username'];
	$getBudget = mysqli_query($conn, "SELECT * FROM expenses WHERE username = '$username' ORDER BY date DESC");
	if(mysqli_num_rows($getBudget)>0){
		echo '<div id = "budgetListDiv">
		<div class = "expensesListHeading">
			<div class = "listMainHeadingName">Budget List </div>
			<div class = "closeButton" onclick = "hideListDivs()">Close</div>
		</div>
		<div class="tableContainer"><table class="analysisTable">
		<thead>
			<tr>
				<th>Date</th><th>Category</th><th>Money</th><th>Type</th><th>Details</th><th>Budget</th><th>Edit</th><th>Delete</th>
			</tr>
		</thead>
		<tbody>';

		while($rowBudget = mysqli_fetch_assoc($getBudget)){
			echo '<tr>
				<td>'.date('d-M-Y (D)', strtotime($rowBudget['date'])).'</td><td>'.ucwords($rowBudget['category']).'</td>
				<td>&#8377;<span ';
					if($rowBudget['type'] == 'income'){
						echo ' class = "successMessage"';
					}else{
						echo ' class = "errorMessage"';
					}
					echo ' >'.number_format($rowBudget['amount']).'
				</span></td>
				<td>'.ucwords($rowBudget['type']).'</td>
				<td>'.$rowBudget['details'].'</td>';
				if($rowBudget['Budget']!=''&&$rowBudget['Budget']!='noBudgetRegd'){echo'<td>'.$rowBudget['Budget'].'</td>';}else{echo'<td>-</td>';}
				echo'<td><button class = "expensesEditButton" onclick="editExpenses('.$rowBudget['id'].')">Edit</button></td>
				<td><button class = "expensesDeleteButton" onclick="deleteExpenses('.$rowBudget['id'].')">Delete</button></td>
			</tr>';

		}
		echo'</tbody>
		</table></div>';
	}else{
		echo 'No expenses added!';
	}
	echo '</div>';
}

/*Show Edit Expenses*/
if(isset($_POST['showEditExpenses'])){
	$expensesId = $_POST['expensesId'];
	$checkExpenses = mysqli_query($conn, "SELECT * FROM expenses WHERE id = '$expensesId'");
	if(mysqli_num_rows($checkExpenses)>0){
		while($rowExpenses = mysqli_fetch_assoc($checkExpenses)){
			$category = ucwords($rowExpenses['category']);
			echo'<div id="editExpensesOuterDiv" style="z-index: 3;"><div id="editExpensesInnerDiv">
				<h4>Edit Details</h4><hr><br>
				Date: <br><textarea class = "editTextArea" id="editExpenseDate'.$rowExpenses['id'].'" type="date" placeholder = "Edit Date"></textarea><br><br>
				<datalist id = "categoryOptions">
					<option value="food">Food</option>
					<option value="market">Market</option>
					<option value="travel">Travel</option>
					<option value="petrol">Petrol</option>
					<option value="houseWorks">House Works</option>
					<option value="health">Health</option>
					<option value="education">Education</option>
					<option value="personal">Personal / Shopping</option>
					<option value="savings">Savings</option>
					<option value="others">Others</option>
				</datalist>
				Category: <br><textarea class = "editTextArea" id="editExpenseCategory'.$rowExpenses['id'].'" list = "categoryOptions" placeholder = "Edit Category">
				</textarea><br><br>
				Amount: <br><textarea class = "editTextArea" id="editExpenseAmount'.$rowExpenses['id'].'" placeholder = "Edit Amount"></textarea><br><br>
				Budget: <br><textarea class = "editTextArea" id="editExpenseBudget'.$rowExpenses['id'].'" placeholder = "Edit Budget"></textarea><br><br>
				Details: <br><textarea class = "editTextArea" id="editExpenseDetails'.$rowExpenses['id'].'" placeholder="Edit Details"></textarea><br><br>
				<div id="editExpenseErrorMessage"></div><br><br>
				<button class = "confirmButton closeButton" onclick = "confirmEdit('.$expensesId.')">Done</button>
				<button class = "closeButton" onclick = "hideEditExpense()">Cancel</button><br><br><br>
			</div></div>-period-';
			/*Send data to JS*/
			echo $rowExpenses['amount'].'-period-'.
			$rowExpenses['category'].'-period-'.
			date('d-m-Y',strtotime($rowExpenses['date'])).'-period-'.
			$rowExpenses['details'].'-period-'.
			$rowExpenses['Budget'];
		}
	}
}

/*Show Individual Sub Details*/
if(isset($_POST['checkSubDetails'])){
	$username = $_POST['username'];
	$month = $_POST['month'];
	$year = $_POST['year'];
	$category = $_POST['category'];
	$type = $_POST['type'];
	$checkExpenses = mysqli_query($conn, "SELECT * FROM expenses WHERE username = '$username' AND category = '$category' AND month(date) = '$month' AND year(date) = '$year' AND type = '$type' ORDER BY date DESC, id DESC");
	if($category=='others'){
		if($type=='expense'){
			$checkExpenses = mysqli_query($conn, "SELECT * FROM expenses WHERE username = '$username' AND type = '$type' AND month(date) = '$month' AND year(date) = '$year' AND (category != 'food' AND category != 'market' AND category != 'travel' AND category != 'petrol' AND category != 'houseWorks' AND category != 'health' AND category != 'education' AND category != 'personal' AND category != 'savings' AND category != 'office') ORDER BY date DESC, id DESC");
		}else if($type=='income'){
			$checkExpenses = mysqli_query($conn, "SELECT * FROM expenses WHERE username = '$username' AND type = '$type' AND month(date) = '$month' AND year(date) = '$year' AND (category != 'salary' AND category != 'investment' AND category != 'rent' AND category != 'bonus' AND category != 'allowance') ORDER BY date DESC, id DESC");
		}
	}
	if(mysqli_num_rows($checkExpenses)>0){
		echo'<div id="editExpensesOuterDiv" ><div id="editExpensesInnerDiv">
			<div style="font-size:large">More Details:</div><br><br>
			<div class="tableContainer"><table class="analysisTable">
				<thead>
					<tr>
						<th>Date</th><th>Category</th><th>Money</th><th>Details</th><th>Type</th><th>Budget</th><th>Edit</th><th>Delete</th>
					</tr>
				</thead>
				<tbody>';
				while($rowExpenses = mysqli_fetch_assoc($checkExpenses)){
					echo'<tr>
						<td>'.date('d-M-Y (D)', strtotime($rowExpenses['date'])).'</td><td>'.ucwords($rowExpenses['category']).'</td>
						<td>&#8377;<span ';
							if($rowExpenses['type'] == 'income'){
								echo ' class = "successMessage"';
							}else{
								echo ' class = "errorMessage"';
							}
							echo ' >'.number_format($rowExpenses['amount']).'
						</span></td>
						<td>';
							if($rowExpenses['details']!=''){echo $rowExpenses['details'];}else{echo '-';}
						echo'</td>
						<td>'.ucwords($rowExpenses['type']).'</td>
						<td>';
							if($rowExpenses['Budget']!=''&&$rowExpenses['Budget']!='noBudgetRegd'){echo $rowExpenses['Budget'];}else{echo '-';}
						echo'</td>
						<td><button class = "expensesEditButton" onclick="editExpenses('.$rowExpenses['id'].')">Edit</button></td>
						<td><button class = "expensesDeleteButton" onclick="deleteExpenses('.$rowExpenses['id'].')">Delete</button></td>
					</tr>';
				}
				echo'</tbody>
			</table></div><br><br>
			<button class = "closeButton hideButton" onclick = "hideSubDetailsDiv()">Close</button><br><br><br>
		</div></div>';
	}else{
		echo'<div id="editExpensesOuterDiv" ><div id="editExpensesInnerDiv">
			<br><br>No Details Found..<br><br>
			<button class = "closeButton hideButton" onclick = "hideSubDetailsDiv()">Cancel</button><br><br><br>
		</div></div>';
	}
}

/*Edit Expenses*/
if(isset($_POST['editExpenses'])){
	$expensesId = $_POST['expensesId'];
	$expensesDate = date('Y-m-d',strtotime($_POST['expensesDate']));
	$expensesAmount = $_POST['expensesAmount'];
	$expensesCategory = $_POST['expensesCategory'];
	$expensesDetails = $_POST['expensesDetails'];
	$expensesBudget = $_POST['expensesBudget'];
	$updateExpenses = mysqli_query($conn, "UPDATE expenses SET date = '$expensesDate', amount = '$expensesAmount', category = '$expensesCategory', details = '$expensesDetails', Budget='$expensesBudget' WHERE id = '$expensesId'");
	/*Check Income or Expense*/
	$expensesType = checkIncomeOrExpense($expensesId,$conn);
	/*Check Username*/
	$expensesUsername = checkUsername($expensesId,$conn);
	/*Send Type to JS*/
	echo $expensesType.'-period-';
	/*Get New Expense Value*/
	$getExpenses = mysqli_query($conn,"SELECT * FROM expenses WHERE type = '$expensesType' AND category != 'BudgetTransfer' AND username = '$expensesUsername' ORDER BY id DESC");
	$totalExpenses = 0;
	if(mysqli_num_rows($getExpenses)>0){
		while($rowIncome = mysqli_fetch_assoc($getExpenses)){
			$totalExpenses += $rowIncome['amount'];
		}
		echo number_format($totalExpenses);
	}
}

/*Show Delete Expenses*/
if(isset($_POST['showDeleteExpenses'])){
	$expensesId = $_POST['expensesId'];
	$checkExpenses = mysqli_query($conn, "SELECT * FROM expenses WHERE id = '$expensesId'");
	if(mysqli_num_rows($checkExpenses)>0){
		while($rowExpenses = mysqli_fetch_assoc($checkExpenses)){
			$category = ucwords($rowExpenses['category']);
			echo'<div id="deleteExpensesOuterDiv" style="z-index:3"><div id="deleteExpensesInnerDiv">
				<br><br><div class = "headingName">Delete Details</div><hr><br>
				Are you sure you want to delete this data?<br><br>
				<div>Amount: <b>&#8377;'.number_format($rowExpenses['amount']).'</b></div>
				<div>Category: <b>'.ucwords($rowExpenses['category']).'</b></div>
				<div>Date: <b>'.date('d-M-Y (D)', strtotime($rowExpenses['date'])).'</b></div>
				<div>Details: <b>'.$rowExpenses['details'].'</b><br><br></div>
				<div>Budget: <b>'.$rowExpenses['Budget'].'</b><br><br></div>
				<div id="deleteExpenseErrorMessage"></div><br>
				<button class = "expensesDeleteButton" onclick = "confirmDelete('.$expensesId.')">Delete</button>
				<button class = "expensesEditButton" onclick = "hideDeleteExpense()">Cancel</button>
			</div></div>-period-';
			/*Send data to JS*/
			echo $rowExpenses['amount'].'-period-'.
			$rowExpenses['category'].'-period-'.
			$rowExpenses['date'].'-period-'.
			$rowExpenses['details'].'-period-'.
			$rowExpenses['Budget'];
		}
	}
}

/*Delete Expenses*/
if(isset($_POST['deleteExpenses'])){
	$expensesId = $_POST['expensesId'];
	/*Check Income or Expense*/
	$expensesType = checkIncomeOrExpense($expensesId,$conn);
	/*Check Username*/
	$expensesUsername = checkUsername($expensesId,$conn);

	/*Delete Now*/
	$updateExpenses = mysqli_query($conn, "DELETE FROM expenses WHERE id = '$expensesId'");
	/*Get New Expense Value*/
	$getExpenses = getNewExpenseDetails($conn, $expensesUsername, $expensesType);

	/*Calculate Budget*/
	if($expensesType == 'expense'){
		$getIncome = getNewExpenseDetails($conn, $expensesUsername, 'income');
		$getBudget = $getIncome - $getExpenses;
	}else{
		$getExpense = getNewExpenseDetails($conn, $expensesUsername, 'expense');
		$getBudget = $getExpenses - $getExpense;
	}

	echo $expensesType.'-period-';
	echo number_format($getExpenses).'-period-';
	echo number_format($getBudget);
}

/*Search Expenses*/
if(isset($_POST['searchExpenses'])){
	$searchq = mysqli_real_escape_string($conn, $_POST['searchq']);
	$username = $_POST['username'];
	$searchExpenses = mysqli_query($conn, "SELECT * FROM expenses WHERE (username = '$username') AND (amount LIKE '%$searchq%' OR type LIKE '%$searchq%' OR date LIKE '%$searchq%' OR category LIKE '%$searchq%' OR details LIKE '%$searchq%' OR Budget LIKE '%$searchq%') ORDER BY date DESC, id DESC");
	$totalSearchExpenses = mysqli_query($conn, "SELECT SUM(amount) AS searchExpense FROM expenses WHERE (amount LIKE '%$searchq%' OR type LIKE '%$searchq%' OR date LIKE '%$searchq%' OR category LIKE '%$searchq%' OR details LIKE '%$searchq%' OR Budget LIKE '%$searchq%') AND (type = 'expense') AND (username = '$username') ORDER BY date DESC, id DESC");
	$totalSearchIncome = mysqli_query($conn, "SELECT SUM(amount) AS searchIncome FROM expenses WHERE (amount LIKE '%$searchq%' OR type LIKE '%$searchq%' OR date LIKE '%$searchq%' OR category LIKE '%$searchq%' OR details LIKE '%$searchq%' OR Budget LIKE '%$searchq%') AND (type = 'income') AND (username = '$username') ORDER BY date DESC, id DESC");
	if(mysqli_num_rows($searchExpenses)>0){
		echo '<div id = "searchContentDiv">
			<div id = "searchHeadingDiv">'.mysqli_num_rows($searchExpenses).' results have been found with '.$searchq.'<br><br>
				<div style = "font-size:large">
					In this search results:<br>';
					$overallSearchIncome = 0; $overallSearchExpense = 0;
					if (mysqli_num_rows($totalSearchExpenses)>0) {
						while($rowSearchExpenseAmount = mysqli_fetch_assoc($totalSearchExpenses)){
							if($rowSearchExpenseAmount['searchExpense'] != ''){
								echo '<div> Overall Expense: &#8377;<span class = "errorMessage">'.number_format($rowSearchExpenseAmount['searchExpense']).'</span></div>';
								$overallSearchExpense = $rowSearchExpenseAmount['searchExpense'];
							}
						}
					}
					if (mysqli_num_rows($totalSearchIncome)>0) {
						while($rowSearchIncomeAmount = mysqli_fetch_assoc($totalSearchIncome)){
							if($rowSearchIncomeAmount['searchIncome'] != ''){
								echo '<div> Overall Income: &#8377;<span class = "successMessage">'.number_format($rowSearchIncomeAmount['searchIncome']).'</span></div>';
								$overallSearchIncome = $rowSearchIncomeAmount['searchIncome'];
							}
						}
					}
					if ((mysqli_num_rows($totalSearchExpenses)>0) || (mysqli_num_rows($totalSearchIncome)>0)) {
						$netFlow = $overallSearchIncome - $overallSearchExpense;
					}
					echo '<div> Net Flow: &#8377;<span class = "bufferMessage">'.number_format($netFlow).'</span></div>';
				echo '</div><br><br>
				&#8593; = Income &nbsp;&nbsp;&nbsp; &#8595; = Expense
			<div>';
		while ($rowSearch = mysqli_fetch_assoc($searchExpenses)) {
			echo '<div class = "expensesSearchList">
				<div>
					<span>
						'.date('d-M-Y (D)', strtotime($rowSearch['date'])).'
					</span>
					-
					<i>
						'.ucwords($rowSearch['category']).'
					</i>
					 -
					<b>
						&#8377;
						<span ';
							if($rowSearch['type'] == 'income'){
								echo ' class = "successMessage"';
							}else{
								echo ' class = "errorMessage"';
							}
							echo ' >'.number_format($rowSearch['amount']);
						echo'</span>
					</b>';
					if($rowSearch['type'] == 'income'){
						echo '&#8593;';
					}else{
						echo '&#8595;';
					}
					echo '<div>'.$rowSearch['details'].'</div>';
					if($rowSearch['Budget']!=''&&$rowSearch['Budget']!='noBudgetRegd'){echo '<div class="sideHeading">Budget: '.$rowSearch['Budget'].'</div>';}
				echo'</div>
				<div>
					<button class = "expensesEditButton" onclick="editExpenses('.$rowSearch['id'].')">Edit</button>
					<button class = "expensesDeleteButton" onclick="deleteExpenses('.$rowSearch['id'].')">Delete</button>
				</div>
			</div>';
		}
		echo '</div>';
	}else{
		echo '<div id = "searchContentDiv"><div id = "searchHeadingDiv">No results found..</div></div>';
	}
}

/*Filter Expenses*/
if(isset($_POST['filterExpenses'])){
	$fromDate = mysqli_real_escape_string($conn, $_POST['fromDate']);
	$toDate = mysqli_real_escape_string($conn, $_POST['toDate']);
	$username = $_POST['username'];
	$filterExpenses = mysqli_query($conn, "SELECT * FROM expenses WHERE (date BETWEEN '$fromDate' AND '$toDate') AND (username = '$username') ORDER BY date DESC, id DESC");
	$totalFilterExpenses = mysqli_query($conn, "SELECT SUM(amount) AS filterExpense FROM expenses WHERE (date BETWEEN '$fromDate' AND '$toDate') AND (type = 'expense') AND (username = '$username') ORDER BY date DESC, id DESC");
	$totalFilterIncome = mysqli_query($conn, "SELECT SUM(amount) AS filterIncome FROM expenses WHERE (date BETWEEN '$fromDate' AND '$toDate') AND (type = 'income') AND (username = '$username') ORDER BY date DESC, id DESC");
	if(mysqli_num_rows($filterExpenses)>0){
		echo '<div id = "filterContentDiv">
			<div id = "filterHeadingDiv">
				<div>'.mysqli_num_rows($filterExpenses).' results have been found between '.date('d-M-Y', strtotime($fromDate)).' and '.date('d-M-Y', strtotime($toDate)).'. </div><br>
				<div style = "font-size:large">
					In this period:<br>';
					if (mysqli_num_rows($totalFilterExpenses)>0) {
						while($rowfilterExpenseAmount = mysqli_fetch_assoc($totalFilterExpenses)){
							if($rowfilterExpenseAmount['filterExpense'] != ''){
								echo '<div> Overall Expense: &#8377;<span class = "errorMessage">'.number_format($rowfilterExpenseAmount['filterExpense']).'</span></div>';
							}
						}
					}
					if (mysqli_num_rows($totalFilterIncome)>0) {
						while($rowfilterIncomeAmount = mysqli_fetch_assoc($totalFilterIncome)){
							if($rowfilterIncomeAmount['filterIncome'] != ''){
								echo '<div> Overall Income: &#8377;<span class = "successMessage">'.number_format($rowfilterIncomeAmount['filterIncome']).'</span></div>';
							}
						}
					}
				echo '</div>
				<br><br>&#8593; = Income &nbsp;&nbsp;&nbsp; &#8595; = Expense';
			echo '</div>';
			while ($rowFilter = mysqli_fetch_assoc($filterExpenses)) {
				echo '<div class = "expensesSearchList">
					<div>
						<span>
							'.date('d-M-Y (D)', strtotime($rowFilter['date'])).'
						</span>
						-
						<i>
							'.ucwords($rowFilter['category']).'
						</i>
						 -
						<b>
							&#8377;
							<span ';
								if($rowFilter['type'] == 'income'){
									echo ' class = "successMessage"';
								}else{
									echo ' class = "errorMessage"';
								}
								echo ' >'.number_format($rowFilter['amount']);
							echo'</span>
						</b>';
						if($rowFilter['type'] == 'income'){
							echo '&#8593;';
						}else{
							echo '&#8595;';
						}
						echo '<div>'.$rowFilter['details'].'</div>';
						if($rowFilter['Budget']!=''&&$rowFilter['Budget']!='noBudgetRegd'){echo '<div class="sideHeading">Budget: '.$rowFilter['Budget'].'</div>';}
					echo'</div>
					<div>
						<button class = "expensesEditButton" onclick="editExpenses('.$rowFilter['id'].')">Edit</button>
						<button class = "expensesDeleteButton" onclick="deleteExpenses('.$rowFilter['id'].')">Delete</button>
					</div>
				</div>';
			}
		echo '</div>';
	}else{
		echo 'No expenses added on this date..';
	}
}

/*Profile Details*/
if(isset($_POST['getProfileDetails'])){
	$username = $_POST['username'];
	$getDetails = mysqli_query($conn, "SELECT * FROM users WHERE username = '$username'");
	if(mysqli_num_rows($getDetails)>0){
		while($rowDetails = mysqli_fetch_assoc($getDetails)){
			echo'<div id="profileOuterDiv" ><div id="profileInnerDiv">
				<br><br><div class = "headingName">Profile Details</div><hr><br>
				Username  <b>: '.$rowDetails['username'].'</b><button id = "editProfileusername" class = "editButton" onclick = "editProfile(\''.'username'.'-period-'.$rowDetails['username'].'-period-'.$rowDetails['id'].'\')">edit</button><br>
				Password  <b>: ********</b><button id = "editProfilepassword" class = "editButton" onclick = "editProfile(\''.'password'.'-period-'.$rowDetails['password'].'-period-'.$rowDetails['id'].'\')">edit</button><br>
				Email ID  <b>: '.$rowDetails['email'].'</b><button id = "editProfileemail" class = "editButton" onclick = "editProfile(\''.'email'.'-period-'.$rowDetails['email'].'-period-'.$rowDetails['id'].'\')">edit</button><br>
				Name : <b>'.ucwords($rowDetails['name']).'</b><button id = "editProfilename" class = "editButton" onclick = "editProfile(\''.'name'.'-period-'.$rowDetails['name'].'-period-'.$rowDetails['id'].'\')">edit</button><br><br><br>
				<button class = "closeButton" onclick = "hideProfileDetails()">Close</button>
			</div></div>';
		}
	}
}


/*Edit Profile*/
if(isset($_POST['editProfile'])){
	$type = $_POST['type'];
	$value = $_POST['value'];
	$id = $_POST['id'];
	echo '<div id="editProfileOuterDiv" ><div id="editProfileInnerDiv"><br><br>
		Edit the '.ucwords($type).'<br><br>
		<input type = "hidden" id = "oldValueEditProfileDetail'.$type.'" value = "'.$value.'">
		<textarea type = "text" id = "newValueEditProfileDetail'.$type.'" placeholder="Edit '.$type.'"></textarea>
		<div id = "editProfileStatus"></div><br><br><br>
		<button class = "closeButton confirmButton" id="confirmEditProfileDetailsButton" onclick = "confirmEditProfileDetails(\''.$type.'\',\''.$id.'\')">Done</button>
		<button class = "closeButton" onclick = "hideEditProfileDetails()">Cancel</button>
	</div></div>';
}

if (isset($_POST['confirmEditProfile'])) {
	$type = $_POST['type'];
	$id = $_POST['id'];
	$newValue = mysqli_real_escape_string($conn, $_POST['newValue']);
	$oldValue = $_POST['oldValue'];
	$updateCheck = 0;
	if($type == 'email'){
		$checkEmail = mysqli_query($conn, "SELECT * FROM users WHERE email = '$newValue'");
		if(mysqli_num_rows($checkEmail)<1){
			$updateCheck = 1;
		}else{
			echo 'AlreadyExists';
		}
	}else if($type == 'username'){
		$checkUsername = mysqli_query($conn, "SELECT * FROM users WHERE username = '$newValue'");
		if(mysqli_num_rows($checkUsername)<1){
			$updateCheck = 1;
		}else{
			echo 'AlreadyExists';
		}
	}else if($type == 'password' || $type == 'name'){
		$updateCheck = 1;
	}
	if($updateCheck == 1){
		if($type == 'username'){
			$updater = mysqli_query($conn, "UPDATE users SET username = '$newValue' WHERE id = '$id'");
			$updater = mysqli_query($conn, "UPDATE expenses SET username = '$newValue' WHERE username = '$oldValue'");
			$updater = mysqli_query($conn, "UPDATE Budget SET BudgetUsername = '$newValue' WHERE BudgetUsername = '$oldValue'");
			echo 'updateDone';
		}else if($type == 'email'){
			$updater = mysqli_query($conn, "UPDATE users SET email = '$newValue' WHERE id = '$id'");
			echo 'updateDone';
		}else if($type == 'name'){
			$updater = mysqli_query($conn, "UPDATE users SET name = '$newValue' WHERE id = '$id'");
			echo 'updateDone';
		}else if($type == 'password'){
			$updater = mysqli_query($conn, "UPDATE users SET password = '$newValue' WHERE id = '$id'");
			echo 'updateDone';
		}
	}
}

/*Get Today's data*/
if(isset($_POST['getExpenseForNotif'])){
	$todayDate = date('Y-m-d',time());
	$username = $_POST['username'];
	/*$yesterdayDate = date('Y-m-d', strtotime("-1 days"));
	$thisMonth = date('m',time());
	$thisYear = date('Y',time());*/
	$totalTodayExpenses = 0; $totalTodayIncome = 0;

	/*Calculate Today Expenses*/
	$getTodayExpense = mysqli_query($conn,"SELECT SUM(amount) AS todayExpense FROM expenses WHERE type = 'expense' AND username = '$username' AND date = '$todayDate' ORDER BY id DESC");
	$getTodayExpenseCount = mysqli_query($conn,"SELECT * FROM expenses WHERE type = 'expense' AND username = '$username' AND date = '$todayDate' ORDER BY id DESC");
	if(mysqli_num_rows($getTodayExpenseCount)>0){while($rowTodayExpense = mysqli_fetch_assoc($getTodayExpense)){$totalTodayExpenses = $rowTodayExpense['todayExpense'];}}

	/*Calculate Today Incomes*/
	$getTodayIncome = mysqli_query($conn,"SELECT SUM(amount) AS todayIncome FROM expenses WHERE type = 'income' AND username = '$username' AND date = '$todayDate' ORDER BY id DESC");
	$getTodayIncomeCount = mysqli_query($conn,"SELECT * FROM expenses WHERE type = 'income' AND username = '$username' AND date = '$todayDate' ORDER BY id DESC");
	if(mysqli_num_rows($getTodayIncomeCount)>0){while($rowTodayIncome = mysqli_fetch_assoc($getTodayIncome)){$totalTodayIncome = $rowTodayIncome['todayIncome'];}}

	echo $totalTodayExpenses.'-period-'.$totalTodayIncome;

}

/*Add Budget*/
if(isset($_POST['addBudget'])){
	$username = mysqli_real_escape_string($conn,$_POST['username']);
	$BudgetName = mysqli_real_escape_string($conn,$_POST['BudgetName']);
	$BudgetAmount = mysqli_real_escape_string($conn,$_POST['BudgetAmount']);
	$checkBudget = mysqli_query($conn,"SELECT * FROM Budget WHERE BudgetName = '$BudgetName' AND BudgetUsername = '$username'");
	if(mysqli_num_rows($checkBudget)>0){
		echo 'registeredAlready';
	}else{
		if(strpos($BudgetName, 'bank')!==false){
			$BudgetNewName=str_replace('BANK', 'Bank', strtoupper($BudgetName));
		}else{
			$BudgetNewName=ucwords($BudgetName);
		}
		$addBudget = mysqli_query($conn,"INSERT INTO Budget (BudgetUsername, BudgetName, BudgetValue) VALUES ('$username', '$BudgetNewName', '$BudgetAmount')");
		echo 'success';
		echo '-period-';
		getBudgetRadioButton($conn,$username);
		echo'-period-';
		getBudgetSummary($conn,$username);
		echo'-period-';
		getBudgetTransferInfo($conn,$username);
	}
}

/*Set select Budget options*/
if(isset($_POST['setBudgetSelection'])){
	$username = mysqli_real_escape_string($conn,$_POST['username']);
	$selectedValue = mysqli_real_escape_string($conn,$_POST['selectedValue']);
	$getBudgetOptions=mysqli_query($conn,"SELECT * FROM Budget WHERE BudgetUsername='$username'");
	if(mysqli_num_rows($getBudgetOptions)>0){echo'<option value="">Select</option>';
		while($rowBudget=mysqli_fetch_assoc($getBudgetOptions)){
			if($rowBudget['BudgetName']!=$selectedValue){
				echo '<option value="'.$rowBudget['BudgetName'].'">'.$rowBudget['BudgetName'].'</option>';
			}
		}
	}
}

/*Exchange Budget Amount*/
if(isset($_POST['exchangeBudgetAmount'])){
	$username = mysqli_real_escape_string($conn,$_POST['username']);
	$BudgetCredit = mysqli_real_escape_string($conn,$_POST['BudgetCredit']);
	$BudgetDebit = mysqli_real_escape_string($conn,$_POST['BudgetDebit']);
	$BudgetExchangeAmount = mysqli_real_escape_string($conn,$_POST['BudgetExchangeAmount']);
	$date = mysqli_real_escape_string($conn,$_POST['BudgetExchangeDate']);
	//$date=date('Y-m-d', time());
	/*Get Credit Balance*/
	$checkBudgetCreditBal=mysqli_query($conn,"SELECT * FROM Budget WHERE BudgetUsername='$username' AND BudgetName='$BudgetCredit' LIMIT 1");
	if(mysqli_num_rows($checkBudgetCreditBal)>0){
		while($rowBudgetBal=mysqli_fetch_assoc($checkBudgetCreditBal)){
			$BudgetCreditBalance=$rowBudgetBal['BudgetValue'];
		}
	}
	/*Check Debit Balance*/
	$checkBudgetDebitBal=mysqli_query($conn,"SELECT * FROM Budget WHERE BudgetUsername='$username' AND BudgetName='$BudgetDebit' LIMIT 1");
	if(mysqli_num_rows($checkBudgetDebitBal)>0){
		while($rowBudgetBal=mysqli_fetch_assoc($checkBudgetDebitBal)){
			if($rowBudgetBal['BudgetValue']>=$BudgetExchangeAmount){
				$newDebitBal=$rowBudgetBal['BudgetValue']-$BudgetExchangeAmount;
				$newCreditBal=$BudgetCreditBalance+$BudgetExchangeAmount;
				mysqli_query($conn,"UPDATE Budget SET BudgetValue='$newDebitBal' WHERE BudgetUsername='$username' AND BudgetName='$BudgetDebit'");
				mysqli_query($conn,"UPDATE Budget SET BudgetValue='$newCreditBal' WHERE BudgetUsername='$username' AND BudgetName='$BudgetCredit'");
				mysqli_query($conn, "INSERT INTO Budgethistory (BudgetUsername, BudgetNameFrom, BudgetNameTo, BudgetValue, BudgetTransferDate, type, category, details) VALUES ('$username', '$BudgetDebit', '$BudgetCredit', '$BudgetExchangeAmount', '$date', 'BudgetTransfer', 'BudgetTransfer', 'BudgetTransfer')");
				/*Update in expenses database*/
				$newDetails='Budget Amount Transfered from '.$BudgetDebit.' to '.$BudgetCredit;
				//Budget expense for Debit Budget
				mysqli_query($conn,"INSERT INTO expenses (username, type, amount, date, category, Budget, details) VALUES ('$username', 'expense', '$BudgetExchangeAmount', '$date', 'BudgetTransfer', '$BudgetDebit', '$newDetails')");
				//Budget income for Credit Budget
				mysqli_query($conn,"INSERT INTO expenses (username, type, amount, date, category, Budget, details) VALUES ('$username', 'income', '$BudgetExchangeAmount', '$date', 'BudgetTransfer', '$BudgetCredit', '$newDetails')");
				echo'exchangeDone';
				echo'-period-';
				getBudgetSummary($conn,$username);
				echo'-period-';
				getBudgetHistory($conn,$username);
			}else{
				echo'insufficientBalance';
			}
		}
	}
}

/*Show Edit Budget*/
if(isset($_POST['showEditBudget'])){
	$BudgetId = $_POST['BudgetId'];
	$checkBudget = mysqli_query($conn, "SELECT * FROM Budget WHERE id = '$BudgetId'");
	if(mysqli_num_rows($checkBudget)>0){
		while($rowBudget = mysqli_fetch_assoc($checkBudget)){
			echo'<div class="editOuterDiv" style="z-index: 3;"><div class="editInnerDiv">
				<h4>Edit Details</h4><hr><br>
				Budget Name: <br><textarea class = "editTextArea" id="editBudgetName'.$rowBudget['id'].'" type="date" placeholder = "Edit Budget Name"></textarea><br><br>
				Budget Amount: <br><textarea class = "editTextArea" id="editBudgetAmount'.$rowBudget['id'].'" placeholder = "Edit Amount"></textarea><br><br>
				<div id="editBudgetErrorMessage"></div><br><br>
				<button class = "confirmButton closeButton" onclick = "confirmEditBudget(\''.$BudgetId.'\',\''.$rowBudget['BudgetName'].'\')">Done</button>
				<button class = "closeButton" onclick = "hideEditBudget()">Cancel</button><br><br><br>
			</div></div>-period-';
			/*Send data to JS*/
			echo $rowBudget['BudgetName'].'-period-'.
			$rowBudget['BudgetValue'];
		}
	}
}

/*Edit Budget*/
if(isset($_POST['editBudget'])){
	$BudgetId = $_POST['BudgetId'];
	$BudgetAmount = $_POST['BudgetAmount'];
	$BudgetName = $_POST['BudgetName'];
	$oldBudgetName = $_POST['oldBudgetName'];
	$username=$_POST['username'];
	$updateBudget = mysqli_query($conn, "UPDATE Budget SET BudgetName = '$BudgetName', BudgetValue = '$BudgetAmount' WHERE id = '$BudgetId'");
	/*Update Budget Edited info in expenses for further reference issue*/
	$updateBudgetEditedInfoInExpense=mysqli_query($conn,"UPDATE expenses SET Budget='$BudgetName' WHERE username='$username' AND Budget='$oldBudgetName'");
	echo'-period-';
	getBudgetSummary($conn,$username);
	echo'-period-';
	getBudgetTransferInfo($conn,$username);
	echo'-period-';
	getBudgetRadioButton($conn,$username);
}

/*Show Delete Budget*/
if(isset($_POST['showDeleteBudget'])){
	$BudgetId = $_POST['BudgetId'];
	$checkBudget = mysqli_query($conn, "SELECT * FROM Budget WHERE id = '$BudgetId'");
	if(mysqli_num_rows($checkBudget)>0){
		while($rowBudget = mysqli_fetch_assoc($checkBudget)){
			$BudgetName=$rowBudget['BudgetName'];
			$username=$rowBudget['BudgetUsername'];
			$BudgetTransactions='no';
			/*Check for Budget transactions*/
			$checkBudgetExpenses=mysqli_query($conn,"SELECT * FROM expenses WHERE Budget='$BudgetName' AND username='$username'");
			if(mysqli_num_rows($checkBudgetExpenses)>0){
				$BudgetTransactions='yes';
			}
			echo'<div class="editOuterDiv" style="z-index:3"><div class="editInnerDiv">
				<br><br><div class = "headingName">Delete Details</div><hr><br>
				Are you sure you want to delete this Budget?<br><br>';
				echo'<div>Budget Name: <b>'.$rowBudget['BudgetName'].'</b></div>
				<div>Current Value: <b>&#8377;'.number_format($rowBudget['BudgetValue']).'</b></div><br>';
				if($BudgetTransactions=='yes'){
					echo'<div class="errorMessageShake">
						There are already some transactions / expenses registered with '.$BudgetName.'.<br>
						If this Budget is deleted, the Budget-name would be deleted from the registered <b>expense Info</b>!
					</div>';
				}
				echo'<div id="deleteBudgetErrorMessage"></div><br>
				<button class="redButton" onclick = "confirmDeleteBudget(\''.$BudgetId.'\',\''.$BudgetName.'\')">Delete</button>
				<button class="redButtonOuter" onclick = "hideDeleteBudget()">Cancel</button>
			</div></div>';
		}
	}
}

/*Delete Budget*/
if(isset($_POST['deleteBudget'])){
	$BudgetId = $_POST['BudgetId'];
	$username = $_POST['username'];
	$BudgetName = $_POST['BudgetName'];
	$newBudgetName=$BudgetName.'-Deleted';
	$updateBudget = mysqli_query($conn, "DELETE FROM Budget WHERE id = '$BudgetId'");
	/*Update Budget deleted info in expenses for further reference issue*/
	$updateBudgetDeletedInfoInExpense=mysqli_query($conn,"UPDATE expenses SET Budget='$newBudgetName' WHERE username='$username' AND Budget='$BudgetName'");
	/*Get New List*/
	getBudgetSummary($conn,$username);
	echo'-period-';
	getBudgetTransferInfo($conn,$username);
	echo'-period-';
	getBudgetRadioButton($conn,$username);
}

/*Show Delete Budget History*/
if(isset($_POST['showDeleteBudgetHistory'])){
	$BudgetId = $_POST['BudgetId'];
	$checkBudget = mysqli_query($conn, "SELECT * FROM Budgethistory WHERE id = '$BudgetId'");
	if(mysqli_num_rows($checkBudget)>0){
		while($rowBudget = mysqli_fetch_assoc($checkBudget)){
			$BudgetName=$rowBudget['BudgetName'];
			$username=$rowBudget['BudgetUsername'];
			echo'<div class="editOuterDiv" style="z-index:3"><div class="editInnerDiv">
				<br><br><div class = "headingName">Delete Details</div><hr><br>
				Are you sure you want to delete this Budget transaction?<br><br>';
				echo'<div>Transaction From: <b>'.$rowBudget['BudgetNameFrom'].'</b></div>';
				if($rowBudget['BudgetNameTo']!='BudgetExpenseOK'){echo'<div>Transaction To: <b>'.$rowBudget['BudgetNameTo'].'</b></div>';}
				echo'<div>Transaction Amount: <b>&#8377;'.number_format($rowBudget['BudgetValue']).'</b></div>
				<div>Transaction Date: <b>'.date('d-M-Y (l)',strtotime($rowBudget['BudgetTransferDate'])).'</b></div>';
				if($rowBudget['type']!='BudgetTransfer'){
					echo'<div>Transaction Type: <b>'.$rowBudget['type'].'</b></div>
					<div>Transaction Category: <b>'.$rowBudget['category'].'</b></div>
					<div>Transaction Details: <b>'.$rowBudget['details'].'</b></div>';
				}
				echo'<br>
				Only this transaction history will be deleted.. <br>To delete expense amount, select delete option from the expenses list<br>';
				echo'<div id="deleteBudgetErrorMessage"></div><br>
				<button class="redButton" onclick = "confirmDeleteBudgetHistory(\''.$BudgetId.'\')">Delete</button>
				<button class="redButtonOuter" onclick = "hideDeleteBudget()">Cancel</button>
			</div></div>';
		}
	}
}

/*Delete Budget History*/
if(isset($_POST['deleteBudgetHistory'])){
	$BudgetId = $_POST['BudgetId'];
	$username = $_POST['username'];
	$updateBudget = mysqli_query($conn, "DELETE FROM Budgethistory WHERE id = '$BudgetId'");
	/*Get New List*/
	getBudgetSummary($conn,$username);
	echo'-period-';
	getBudgetTransferInfo($conn,$username);
	echo'-period-';
	getBudgetRadioButton($conn,$username);
	echo'-period-';
	getBudgetHistory($conn,$username);
}

/*Show Edit Budget History*/
if(isset($_POST['showEditBudgetHistory'])){
	$BudgetId = $_POST['BudgetId'];
	$checkBudget = mysqli_query($conn, "SELECT * FROM Budgethistory WHERE id = '$BudgetId'");
	if(mysqli_num_rows($checkBudget)>0){
		while($rowBudget = mysqli_fetch_assoc($checkBudget)){
			$BudgetName=$rowBudget['BudgetName'];
			$username=$rowBudget['BudgetUsername'];
			echo'<div class="editOuterDiv" style="z-index:3"><div class="editInnerDiv">
				<br><br><div class = "headingName">Transaction Details</div><hr><br>';
				echo'<div>Transaction From: <textarea class = "editTextArea" id="editBudgetHistoryFrom'.$rowBudget['id'].'"></textarea></div>';
				if($rowBudget['BudgetNameTo']!='BudgetExpenseOK'){echo'<div>Transaction To: <textarea class = "editTextArea" id="editBudgetHistoryTo'.$rowBudget['id'].'"></textarea></div>';}
				echo'<div>Transaction Amount: &#8377;<textarea class = "editTextArea" id="editBudgetHistoryValue'.$rowBudget['id'].'"></textarea></div>
				<div>Transaction Date: <textarea class = "editTextArea" id="editBudgetHistoryDate'.$rowBudget['id'].'"></textarea></div>';
				if($rowBudget['type']!='BudgetTransfer'){
					echo'<div>Transaction Type: <textarea class = "editTextArea" id="editBudgetHistoryType'.$rowBudget['id'].'"></textarea></div>
					<div>Transaction Category: <textarea class = "editTextArea" id="editBudgetHistoryCategory'.$rowBudget['id'].'"></textarea></div>
					<div>Transaction Details: <textarea class = "editTextArea" id="editBudgetHistoryDetails'.$rowBudget['id'].'"></textarea></div>';
				}
				echo '<input type="hidden" id="currentBudgetValue'.$rowBudget['id'].'" value="'.$rowBudget['BudgetValue'].'">';//Send Current Budget Value to JS
				echo'<div id="editBudgetErrorMessage"></div><br>
				<button class="greenButton" onclick = "confirmEditBudgetHistory(\''.$BudgetId.'\')">Done</button>
				<button class="redButtonOuter" onclick = "hideEditBudget()">Cancel</button>
			</div></div>';
			//Send Data to JS
			echo'-period-';
			echo $rowBudget['BudgetNameFrom'];
			echo'-period-';
			echo $rowBudget['BudgetNameTo'];
			echo'-period-';
			echo $rowBudget['BudgetValue'];
			echo'-period-';
			echo date('d-m-Y',strtotime($rowBudget['BudgetTransferDate']));
			echo'-period-';
			echo $rowBudget['type'];
			echo'-period-';
			echo $rowBudget['category'];
			echo'-period-';
			echo $rowBudget['details'];
		}
	}
}

/*Confirm Edit Budget History*/
if(isset($_POST['editBudgetHistory'])){
	$BudgetId = $_POST['BudgetId'];
	$BudgetHistoryFrom = $_POST['BudgetHistoryFrom'];
	$BudgetHistoryTo = $_POST['BudgetHistoryTo'];
	$BudgetHistoryValue = $_POST['BudgetHistoryValue'];
	$BudgetHistoryDate = date('Y-m-d',strtotime($_POST['BudgetHistoryDate']));
	$BudgetHistoryType = $_POST['BudgetHistoryType'];
	$BudgetHistoryCategory = $_POST['BudgetHistoryCategory'];
	$BudgetHistoryDetails = $_POST['BudgetHistoryDetails'];
	$bufferBudgetValue = $_POST['bufferBudgetValue'];
	$username = $_POST['username'];

	$updateExpenses = mysqli_query($conn, "UPDATE Budgethistory SET BudgetNameFrom = '$BudgetHistoryFrom', BudgetNameTo = '$BudgetHistoryTo', BudgetValue = '$BudgetHistoryValue', BudgetTransferDate = '$BudgetHistoryDate', type='$BudgetHistoryType', category='$BudgetHistoryCategory', details='$BudgetHistoryDetails' WHERE id = '$BudgetId'");
	if($BudgetHistoryTo == 'BudgetExpenseOK'){
		$OldBudgetValFrom = getBudgetValue($conn,$username,$BudgetHistoryFrom);
		$NewBudgetValFrom = $OldBudgetValFrom + $bufferBudgetValue;
		mysqli_query($conn, "UPDATE Budget SET BudgetValue='$NewBudgetValFrom' WHERE BudgetName = '$BudgetHistoryFrom' AND BudgetUsername = '$username'");
	}else{
		$OldBudgetValFrom = getBudgetValue($conn,$username,$BudgetHistoryFrom);
		$NewBudgetValFrom = $OldBudgetValFrom + $bufferBudgetValue;
		$OldBudgetValTo = getBudgetValue($conn,$username,$BudgetHistoryTo);
		$NewBudgetValTo = $OldBudgetValTo - $bufferBudgetValue;
		mysqli_query($conn, "UPDATE Budget SET BudgetValue='$NewBudgetValTo' WHERE BudgetName = '$BudgetHistoryTo' AND BudgetUsername = '$username'");
		mysqli_query($conn, "UPDATE Budget SET BudgetValue='$NewBudgetValFrom' WHERE BudgetName = '$BudgetHistoryFrom' AND BudgetUsername = '$username'");
	}

}

/*Update Website*/
if(isset($_POST['updateWebsite'])){
	//Check if to be updated
	$checkUpdateStatus='';$accountType='';
	$username=$_POST['username'];
	$sqlChkVer=mysqli_query($conn,"SELECT * FROM version WHERE username='$username'");
	if(mysqli_num_rows($sqlChkVer)>0){
		$accountType='found';
		while($rowChkVer=mysqli_fetch_assoc($sqlChkVer)){
			if($rowChkVer['version']!='2.0'){
				$checkUpdateStatus='ToBeUpdated';
			}
		}
	}else{
		$checkUpdateStatus='ToBeUpdated';
		$accountType='notFound';
	}
	//Update Table from Budget history to expenses
	if($checkUpdateStatus=='ToBeUpdated'){
		//Get each data from BudgetHistory
		$getBudgetHistoryData=mysqli_query($conn,"SELECT * FROM Budgethistory WHERE BudgetUsername='$username' AND BudgetNameTo!='BudgetExpenseOK'");
		if(mysqli_num_rows($getBudgetHistoryData)>0){
			echo 'UpdateDone';
			while($rowEachBudgetHistoryData=mysqli_fetch_assoc($getBudgetHistoryData)){
				//Insert this row into expenses
				$BudgetAmount=$rowEachBudgetHistoryData['BudgetValue'];
				$date=$rowEachBudgetHistoryData['BudgetTransferDate'];
				$BudgetDebit=$rowEachBudgetHistoryData['BudgetNameFrom'];
				$BudgetCredit=$rowEachBudgetHistoryData['BudgetNameTo'];
				$BudgetTransferDetails='Budget Amount Transfered from '.$BudgetDebit.' to '.$BudgetCredit;
				//Expense
				mysqli_query($conn,"INSERT INTO expenses (username, type, amount, date, category, Budget, details) VALUES ('$username', 'expense', '$BudgetAmount', '$date', 'BudgetTransfer', '$BudgetDebit', '$BudgetTransferDetails')");
				//Income
				mysqli_query($conn,"INSERT INTO expenses (username, type, amount, date, category, Budget, details) VALUES ('$username', 'income', '$BudgetAmount', '$date', 'BudgetTransfer', '$BudgetCredit', '$BudgetTransferDetails')");
			}
			//Update Version
			if($accountType=='found'){
				mysqli_query($conn,"UPDATE version SET version='2.0' WHERE username='$username'");
			}else if($accountType=='notFound'){
				mysqli_query($conn,"INSERT INTO version (username, version) VALUES ('$username', '2.0')");
			}
		}else{
			echo 'UpdateNotDone';
		}
	}
}

//Update Budget details on click
if (isset($_POST['updateBudgetDetails'])) {
	$username=$_POST['username'];
	getBudgetSummary($conn,$username);
	echo'-period-';
	getBudgetHistory($conn,$username);
}

/*Statement Expenses*/
if(isset($_POST['statementExpenses'])){
	$fromDate = mysqli_real_escape_string($conn, $_POST['fromDate']);
	$toDate = mysqli_real_escape_string($conn, $_POST['toDate']);
	$username = $_POST['username'];
	$statementExpenses = mysqli_query($conn, "SELECT * FROM expenses WHERE (date BETWEEN '$fromDate' AND '$toDate') AND (username = '$username') AND (category!='BudgetTransfer') ORDER BY date DESC, id DESC");
	$totalStatementExpenses = mysqli_query($conn, "SELECT SUM(amount) AS statementExpense FROM expenses WHERE (date BETWEEN '$fromDate' AND '$toDate') AND (type = 'expense') AND (username = '$username') AND (category!='BudgetTransfer') ORDER BY date DESC, id DESC");
	$totalStatementIncome = mysqli_query($conn, "SELECT SUM(amount) AS statementIncome FROM expenses WHERE (date BETWEEN '$fromDate' AND '$toDate') AND (type = 'income') AND (username = '$username') AND (category!='BudgetTransfer') ORDER BY date DESC, id DESC");
	$OverallStatementExpenses = 0; $OverallStatementIncome=0; //Initial value
	if(mysqli_num_rows($totalStatementExpenses)>0){
		while($rowTotalStatementExpenses=mysqli_fetch_assoc($totalStatementExpenses)){
			$OverallStatementExpenses = $rowTotalStatementExpenses['statementExpense'];
		}
	}
	if(mysqli_num_rows($totalStatementIncome)>0){
		while($rowTotalStatementIncome=mysqli_fetch_assoc($totalStatementIncome)){
			$OverallStatementIncome = $rowTotalStatementIncome['statementIncome'];
		}
	}
	if(mysqli_num_rows($statementExpenses)>0){
		echo '<div id = "statementListDiv">
		<div class = "expensesListHeading">
			<div class = "listMainHeadingName">Statement List </div>
			<div class = "closeButton" onclick = "hideListDivs()">Close</div>
		</div>
		<div class="tableContainer"><table id="statementTable" class="analysisTable">
		<thead>
			<tr><th colspan="7" style="text-align:center">Statement from '.date('d-M-Y',strtotime($fromDate)).' to '.date('d-M-Y',strtotime($toDate)).'</th></tr>
			<tr><td colspan="7">Total Expenditure: &#8377;'.number_format($OverallStatementExpenses).'</td></tr>
			<tr><td colspan="7">Total Income: &#8377;'.number_format($OverallStatementIncome).'</td></tr>';
			//Get all Budget values
			echo'
			<tr><td colspan="7"></td></tr>
			<tr><th colspan="7">Current Budget Info: </th></tr>
			<tr><td colspan="7">';
			$BudgetCheck=mysqli_query($conn,"SELECT * FROM Budget WHERE BudgetUsername='$username'");
			if(mysqli_num_rows($BudgetCheck)>0){
				while($rowBudgets=mysqli_fetch_assoc($BudgetCheck)){
					echo $rowBudgets['BudgetName'].' - &#8377;'.number_format($rowBudgets['BudgetValue']).'</td></tr><tr><td colspan="7">';
				}
			}else{
				echo 'No Budgets details found!';
			}
			echo'</td></tr>
			<tr><td colspan="7"></td></tr>

			<tr>
				<th>Sl No.</th><th>Date</th><th>Category</th><th>Money</th><th>Details</th><th>Type</th><th>Budget</th>
			</tr>
		</thead>
		<tbody>';

		$scount=1;
		while($rowStatement = mysqli_fetch_assoc($statementExpenses)){
			echo '<tr>
				<td>'.$scount.'</td>
				<td style="width:150px">'.date('d-M-Y (D)', strtotime($rowStatement['date'])).'</td><td>'.ucwords($rowStatement['category']).'</td>
				<td>&#8377;<span ';
					if($rowStatement['type'] == 'income'){
						echo ' class = "successMessage"';
					}else{
						echo ' class = "errorMessage"';
					}
					echo ' >'.number_format($rowStatement['amount']).'
				</span></td>
				<td>'.$rowStatement['details'].'</td>
				<td>'.ucwords($rowStatement['type']).'</td>';
				if($rowStatement['Budget']!=''&&$rowStatement['Budget']!='noBudgetRegd'){echo'<td>'.$rowStatement['Budget'].'</td>';}else{echo'<td>-</td>';}
			echo'</tr>';
			$scount++;
		}
		echo'</tbody>
		</table></div>';
		//echo 'FileDownloaded';
	}else{
		echo 'NoDataFound';
	}
}

/*Income or Expense Checker*/
function checkIncomeOrExpense($id,$conn){
	$checkIE = mysqli_query($conn, "SELECT * FROM expenses WHERE id = '$id'");
	$checkedIE = '';
	if(mysqli_num_rows($checkIE)>0){
		while($rowCheckIE = mysqli_fetch_assoc($checkIE)){
			$checkedIE = $rowCheckIE['type'];
		}
	}
	return $checkedIE;
}

/*Username Checker*/
function checkUsername($id,$conn){
	$checkUsername = mysqli_query($conn, "SELECT * FROM expenses WHERE id = '$id'");
	$checkedUsername = '';
	if(mysqli_num_rows($checkUsername)>0){
		while($rowCheckIE = mysqli_fetch_assoc($checkUsername)){
			$checkedUsername = $rowCheckIE['username'];
		}
	}
	return $checkedUsername;
}

/*Get New Expense Details*/
function getNewExpenseDetails($conn, $username, $type){
	$getNewExpense = mysqli_query($conn,"SELECT * FROM expenses WHERE type = '$type' AND category != 'BudgetTransfer' AND username = '$username' ORDER BY id DESC");
	$totalExpenses = 0;
	if(mysqli_num_rows($getNewExpense)>0){
		while($rowExpense = mysqli_fetch_assoc($getNewExpense)){
			$totalExpenses += $rowExpense['amount'];
		}
	}
	return $totalExpenses;
}

/*Get Username*/
function getUsername($conn, $id){
	$getUsername = mysqli_query($conn,"SELECT * FROM users WHERE id = '$id'");
	if(mysqli_num_rows($getUsername)>0){
		while($rowUsername = mysqli_fetch_assoc($getUsername)){
			$username = $rowUsername['username'];
		}
	}
	return $username;
}

/*Get Budget Summary*/
function getBudgetSummary($conn,$username){
	/*Budget Summary*/
	$BudgetCheck=mysqli_query($conn,"SELECT * FROM Budget WHERE BudgetUsername='$username'");
	if(mysqli_num_rows($BudgetCheck)>0){
		echo'<br><u>Budget Info:</u><br><br>
		<div id="currentBudgets">';
			while($rowBudgets=mysqli_fetch_assoc($BudgetCheck)){
				echo '<div class="eachCurrentBudget">
					<div>'.$rowBudgets['BudgetName'].'</div><hr> &#8377;'.number_format($rowBudgets['BudgetValue']).'<br><br>
					<div>
						<button class="basicButtonOuter smallButton" onclick="showEditBudget(\''.$rowBudgets['id'].'\')">edit</button>
						<button class="redButtonOuter smallButton" onclick="showDeleteBudget(\''.$rowBudgets['id'].'\')">delete</button>
					</div>
				</div>';
			}
		echo'</div>';
		echo'<br><button type="button" class="closeButton" onclick="closeBudgetDiv()">CLOSE</button><br><br>';
	}else{
		echo'<br>No Budgets registered.<br>';
	}
}

/*Get Budget Info*/
function getBudgetInfo($conn,$username){
	$BudgetCheck=mysqli_query($conn,"SELECT * FROM Budget WHERE BudgetUsername='$username'");
	if(mysqli_num_rows($BudgetCheck)>0){
		echo'<u>Current Budgets: </u><br><br>
		<div id="currentBudgets">';
			while($rowBudgets=mysqli_fetch_assoc($BudgetCheck)){
				echo '<div class="eachCurrentBudget">
					'.$rowBudgets['BudgetName'].'<hr>
					<button class="basicButtonOuter smallButton" onclick="showEditBudget(\''.$rowBudgets['id'].'\')">edit</button><br>
					<button class="redButtonOuter smallButton" onclick="showDeleteBudget(\''.$rowBudgets['id'].'\')">delete</button>
				</div>';
			}
		echo'</div>';
	}else{
		echo 'No Budgets registered!';
	}
}

/*Get Budget Selections for transfers*/
function getBudgetTransferInfo($conn,$username){
	$BudgetCheck=mysqli_query($conn,"SELECT * FROM Budget WHERE BudgetUsername='$username'");
	if(mysqli_num_rows($BudgetCheck)>0){
		echo'<br><u>Add Money to Budget:</u><br><br>
		Debit From:<select class="inputStyle" id="BudgetDebit" onchange="setBudgetSelection(\''."debit".'\')"><option value="">Select</option>';
			$BudgetCheck=mysqli_query($conn,"SELECT * FROM Budget WHERE BudgetUsername='$username'");
			while($rowBudgets=mysqli_fetch_assoc($BudgetCheck)){
				echo '<option value="'.$rowBudgets['BudgetName'].'">'.$rowBudgets['BudgetName'].'</option>';
			}
		echo'</select><br>
		Credit To:<select class="inputStyle" id="BudgetCredit" onchange="setBudgetSelection(\''."credit".'\')"><option value="">Select</option>';
			$BudgetCheck=mysqli_query($conn,"SELECT * FROM Budget WHERE BudgetUsername='$username'");
			while($rowBudgets=mysqli_fetch_assoc($BudgetCheck)){
				echo '<option value="'.$rowBudgets['BudgetName'].'">'.$rowBudgets['BudgetName'].'</option>';
			}
		echo'</select><br>
		<input type="date" id="BudgetTransferDate" class="expenseInput inputStyle" value="'.$todayDate.'">
		&#8377;<input type="number" min="0" max="9999999" id="BudgetExchangeAmount" class="inputStyle" placeholder="Amount"><br>
		<div id="BudgetExchangeErrorMessage"></div>
		<button type="button" id="BudgetExchangeSubmit" class="submitButton" onclick="BudgetExchange()">SEND</button>
		<button type="button" class="closeButton" onclick="closeBudgetDiv()">CLOSE</button><br><br>';
	}else{
		echo'<br>No Budgets registered.<br><br>';
	}
}

function getBudgetRadioButton($conn,$username){
	/*Budget List in add expense/income div*/
	/*Expense Budget Options*/
	$getBudget=mysqli_query($conn,"SELECT * FROM Budget WHERE BudgetUsername='$username'");
	if(mysqli_num_rows($getBudget)>0){
		while($rowBudget=mysqli_fetch_assoc($getBudget)){
			$BudgetNameTrim=str_replace(' ', '', $rowBudget['BudgetName']);
			echo '<input type="radio" id="expenseBudget'.$BudgetNameTrim.'" class="inputStyle" name="expenseBudget" value="'.$rowBudget['BudgetName'].'"><label for="expenseBudget'.$BudgetNameTrim.'">'.$rowBudget['BudgetName'].'</label>';
		}
		echo'<input type="radio" id="expenseBudgetCash" class="inputStyle" name="expenseBudget" value="Cash"><label for="expenseBudgetCash">Cash</label>
		<a href="#marquee" type="button" class="greenButtonOuter smallButton">New Budget</a>';
	}
	echo'-period-';/*Income Budget Options*/
	$getBudget=mysqli_query($conn,"SELECT * FROM Budget WHERE BudgetUsername='$username'");
	if(mysqli_num_rows($getBudget)>0){
		while($rowBudget=mysqli_fetch_assoc($getBudget)){
			$BudgetNameTrim=str_replace(' ', '', $rowBudget['BudgetName']);
			echo '<input type="radio" id="incomeBudget'.$BudgetNameTrim.'" class="inputStyle" name="incomeBudget" value="'.$rowBudget['BudgetName'].'"><label for="incomeBudget'.$BudgetNameTrim.'">'.$rowBudget['BudgetName'].'</label>';
		}
		echo'<input type="radio" id="incomeBudgetCash" class="inputStyle" name="incomeBudget" value="Cash"><label for="incomeBudgetCash">Cash</label>
		<a href="#marquee" type="button" class="greenButtonOuter smallButton">New Budget</a>';
	}
}

function getBudgetHistory($conn,$username){
	$BudgetCheckHistory=mysqli_query($conn,"SELECT * FROM Budgethistory WHERE BudgetUsername='$username' ORDER BY BudgetTransferDate DESC");
	if(mysqli_num_rows($BudgetCheckHistory)>0){
		echo'<div>';
			$checkAllBudgets=mysqli_query($conn,"SELECT * FROM Budget WHERE BudgetUsername='$username' ORDER BY BudgetName ASC");
			if(mysqli_num_rows($checkAllBudgets)>0){
				echo'<input id="allBudgetID" type="radio" name="BudgetHistoryFilter" value="all" checked onchange="filterBudgetHistory(\''.'all'.'\')">
					<label for="allBudgetID">All</label>';
				while($rowEachBudget=mysqli_fetch_assoc($checkAllBudgets)){
					echo'<input id="'.$rowEachBudget['BudgetName'].'BudgetID" type="radio" name="BudgetHistoryFilter" value="'.$rowEachBudget['BudgetName'].'"  onchange="filterBudgetHistory(\''.$rowEachBudget['BudgetName'].'\')">
					<label for="'.$rowEachBudget['BudgetName'].'BudgetID">'.$rowEachBudget['BudgetName'].'</label>';
				}
				echo'<br><br>';
			}
		echo'</div>';
		echo'<div class="tableContainer">
		<table class="analysisTable">
			<thead>
				<tr>
					<th>Date</th><th>Amount</th><th>From</th><th>To</th><th>Expense / Income</th><th>Category</th><th>Details</th><th>Edit</th><th>Delete</th>
				</tr>
			</thead>
			<tbody>';
				while($rowBudgetHistory=mysqli_fetch_assoc($BudgetCheckHistory)){
					echo'<tr class="allBudgetHistoryRows '.$rowBudgetHistory['BudgetNameFrom'].'BudgetDetails '.$rowBudgetHistory['BudgetNameTo'].'BudgetDetails">
						<td>'.date('d-M-Y (D)',strtotime($rowBudgetHistory['BudgetTransferDate'])).'</td>';
						echo'<td style="';
						if($rowBudgetHistory['type']=='expense'){echo ' color:red';}
						else if($rowBudgetHistory['type']=='income'){echo' color:green';}
						else{echo ' color:blue';}
						echo' "> &#8377;'.number_format($rowBudgetHistory['BudgetValue']).'</td>';
						echo'<td>'.$rowBudgetHistory['BudgetNameFrom'].'</td>';
						if($rowBudgetHistory['BudgetNameTo']=='BudgetExpenseOK'){echo'<td>-</td>';}else{echo'<td>'.$rowBudgetHistory['BudgetNameTo'].'</td>';}
						if($rowBudgetHistory['type']=='BudgetTransfer'){
							echo'<td>Budget Transfer</td>
							<td>-</td><td>-</td>';
						}else{
							echo'<td>'.$rowBudgetHistory['type'].'</td>
							<td>'.$rowBudgetHistory['category'].'</td><td>'.$rowBudgetHistory['details'].'</td>';
						}
						echo'<td><button class = "expensesEditButton" onclick="editBudgetHistory('.$rowBudgetHistory['id'].')">Edit</button></td>
						<td><button class = "expensesDeleteButton" onclick="deleteBudgetHistory('.$rowBudgetHistory['id'].')">Delete</button></td>
					</tr>';
				}
			echo'</tbody>
			</table>
		</div>';
	}else{
		echo 'No transactions found.';
	}
}

function getBudgetValue($conn,$BudgetUsername,$BudgetName){
	$checkBudgetVal = mysqli_query($conn,"SELECT * FROM Budget WHERE BudgetUsername = '$BudgetUsername' AND BudgetName = '$BudgetName' ORDER BY id DESC LIMIT 1");
	$BudgetVal = '';
	if(mysqli_num_rows($checkBudgetVal)>0){
		while($rowBudget = mysqli_fetch_assoc($checkBudgetVal)){
			$BudgetVal = $rowBudget['BudgetValue'];
		}
	}
	return $BudgetVal;
}

?>
