/*Create a Database*/
CREATE DATABASE expenseManager;

/*User Table*/
CREATE TABLE users (
	id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
	username VARCHAR(256) NOT NULL,
	password VARCHAR(256) NOT NULL,
	name VARCHAR(256) NOT NULL,
	email VARCHAR(256) NOT NULL
);

/*Expense Table*/
CREATE TABLE expenses (
	id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
	username VARCHAR(256) NOT NULL,
	type VARCHAR(256) NOT NULL,
	amount INT(11) NOT NULL,
	date DATETIME NOT NULL,		/*Changed*/
	/*date DATE NOT NULL,*/		/*Old*/
	category VARCHAR(256) NOT NULL,
	Budget VARCHAR(256) NOT NULL,
	details LONGTEXT NOT NULL
);

/*Budget Table*/
CREATE TABLE Budget (
	id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
	BudgetUsername VARCHAR(256) NOT NULL,
	BudgetName VARCHAR(256) NOT NULL,
	BudgetValue INT(11) NOT NULL
);

/*Budget History*/
CREATE TABLE Budgethistory (
	id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
	BudgetUsername VARCHAR(256) NOT NULL,
	BudgetNameFrom VARCHAR(256) NOT NULL,
	BudgetNameTo VARCHAR(256) NOT NULL,
	BudgetValue INT(11) NOT NULL,
	BudgetTransferDate DATETIME NOT NULL,
	type VARCHAR(256) NOT NULL,
	category VARCHAR(256) NOT NULL,
	details LONGTEXT NOT NULL
);

/*Change DATE to DATETIME in date*/
ALTER TABLE 'expenses' CHANGE 'date' 'date' DATETIME NOT NULL; 		/*No need if DATETIME kept in above query*/

/*Website Version*/
CREATE TABLE version (
	id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
	username VARCHAR(256) NOT NULL,
	version VARCHAR(256) NOT NULL
);
