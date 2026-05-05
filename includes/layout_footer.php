<script>
setTimeout(() => {
    document.querySelectorAll('.hrms-toast').forEach(t => {
        t.classList.add('fade-out');
        setTimeout(() => t.remove(), 400);
    });
}, 4000);
function showHrmsModal(title, message, confirmUrl, isDanger = true) {
    document.getElementById('hrmsModalTitle').innerText = title;
    document.getElementById('hrmsModalBody').innerHTML = message;
    const btn = document.getElementById('hrmsModalConfirmBtn');
    btn.href = confirmUrl;
    btn.className = isDanger ? 'btn btn-danger' : 'btn btn-accent';
    document.getElementById('hrmsModalBackdrop').classList.add('show');
}
function closeHrmsModal() { document.getElementById('hrmsModalBackdrop').classList.remove('show'); }
function toggleNotifications() {
    $('#notifDropdown').toggle();
    if($('#notifBadge').length) {
        $.post('<?php echo BASE_URL; ?>/index.php?page=notification&action=markRead', function() {
            $('#notifBadge').fadeOut();
        });
    }
}
</script>
</main>
</div>
</div>
</body>
</html>
