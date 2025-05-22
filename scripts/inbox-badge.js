window.updateInboxBadge = async function() {
  const badge = document.getElementById('inbox-unread-count');
  if (badge) {
    const res = await fetch('/FoundIT/api/inbox.php?ts=' + Date.now());
    const data = await res.json();
    if (data.success) {
      let unreadCount = 0;
      data.messages.forEach(msg => { if (msg.is_read == 0) unreadCount++; });
      if (unreadCount > 0) {
        badge.textContent = unreadCount;
        badge.classList.remove('hidden');
      } else {
        badge.textContent = '';
        badge.classList.add('hidden');
      }
    } else {
      badge.textContent = '';
      badge.classList.add('hidden');
    }
  }
};
document.addEventListener('DOMContentLoaded', window.updateInboxBadge); 