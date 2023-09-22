<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="receipt">
        <h1>Receipt</h1>
        <p><strong>Date:</strong> September 21, 2023</p>
        <p><strong>Receipt Number:</strong> 12345</p>
        <p><strong>Customer Name:</strong> John Doe</p>
        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Quantity</th>
                    <th>Price</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Item 1</td>
                    <td>2</td>
                    <td>$10.00</td>
                    <td>$20.00</td>
                </tr>
                <tr>
                    <td>Item 2</td>
                    <td>1</td>
                    <td>$15.00</td>
                    <td>$15.00</td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3">Subtotal:</td>
                    <td>$35.00</td>
                </tr>
                <tr>
                    <td colspan="3">Tax (10%):</td>
                    <td>$3.50</td>
                </tr>
                <tr>
                    <td colspan="3">Total:</td>
                    <td>$38.50</td>
                </tr>
            </tfoot>
        </table>
    </div>
</body>
</html>
<style>
  body {
    font-family: Arial, sans-serif;
    background-color: #f4f4f4;
    margin: 0;
    padding: 0;
}

.receipt {
    background-color: #fff;
    border: 1px solid #ccc;
    margin: 20px;
    padding: 20px;
    box-shadow: 0 0 5px rgba(0, 0, 0, 0.2);
}

h1 {
    text-align: center;
    color: #333;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

table, th, td {
    border: 1px solid #ccc;
}

th, td {
    padding: 10px;
    text-align: left;
}

thead {
    background-color: #f2f2f2;
}

tfoot {
    font-weight: bold;
}

tfoot td {
    border-top: 2px solid #333;
}

p {
    margin: 10px 0;
}
/* Customize further as needed */
</style>