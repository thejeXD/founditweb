<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Test if PHP is working
echo "<!-- PHP is working -->";
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Registration API</title>
</head>
<body>
    <h1>Test Registration API</h1>
    <div id="result">Testing...</div>

    <script>
        async function testRegistration() {
            const testData = {
                firstName: "Test",
                lastName: "User",
                studentNumber: "1234567890",
                email: "test@example.com",
                password: "Test123!@#"
            };

            try {
                console.log('Sending test data:', testData);
                
                const response = await fetch('register.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(testData)
                });

                console.log('Response received:', response);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                console.log('Parsed response:', data);
                
                document.getElementById('result').innerHTML = `
                    <h2>Response:</h2>
                    <pre>${JSON.stringify(data, null, 2)}</pre>
                `;
            } catch (error) {
                console.error('Error:', error);
                document.getElementById('result').innerHTML = `
                    <h2>Error:</h2>
                    <pre>${error.message}</pre>
                    <p>Check browser console for more details.</p>
                `;
            }
        }

        // Run test when page loads
        testRegistration();
    </script>
</body>
</html> 