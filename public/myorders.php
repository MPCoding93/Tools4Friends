    </main>
    <footer>
        <p>&copy; <span id="year"></span> Tools4Friends</p>
    </footer>
</div>
<script>
    function cancelReservation(availabilityId) {
        if (confirm('<?php echo $lang === 'cs' ? 'Opravdu chcete zrušit tuto rezervaci?' : 'Are you sure you want to cancel this reservation?'; ?>')) {
            fetch('cancel_reservation.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `availability_id=${availabilityId}&lang=<?php echo $lang; ?>`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                alert('<?php echo $lang === 'cs' ? 'Chyba při komunikaci se serverem' : 'Error communicating with server'; ?>');
            });
        }
    }
</script>
</body>
</html
