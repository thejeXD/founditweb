document.querySelector('.login-form').addEventListener('submit', async function(e) {
  e.preventDefault();

  const email = document.getElementById('email').value.trim();
  const password = document.getElementById('password').value;

  const response = await fetch('/FoundIT/auth/login.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ email, password })
  });

  const data = await response.json();

  if (data.success) {
    window.location.href = '/FoundIT/pages/dashboard.html';
  } else {
    alert(data.message);
  }
}); 