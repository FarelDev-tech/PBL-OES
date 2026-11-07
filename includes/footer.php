        </main>
    </div>
</div>


<div class="popup-backdrop modal-backdrop" id="popup-konfirmasi-global">
    <div class="popup-dialog modal-dialog" style="max-width: 440px;">
        <div class="popup-header modal-header">
            <div>
                <h3 class="popup-title modal-title" id="konfirmasi-judul">Konfirmasi</h3>
            </div>
            <button type="button" class="popup-close modal-close-btn" onclick="tutupPopup('popup-konfirmasi-global')">&times;</button>
        </div>

        <p id="konfirmasi-pesan" style="font-size: 13px; color: var(--oes-neutral-700); line-height: 1.6;">
            Apakah Anda yakin ingin melanjutkan tindakan ini?
        </p>

        <form method="post" action="#" id="konfirmasi-form">
            <?php echo csrfField(); ?>
            <div id="konfirmasi-hidden-inputs"></div>

            <div class="popup-footer modal-footer">
                <button type="button" class="btn btn-outline" onclick="tutupPopup('popup-konfirmasi-global')">Batal</button>
                <button type="submit" class="btn btn-dark" id="konfirmasi-btn-submit">Konfirmasi</button>
            </div>
        </form>
    </div>
</div>

<script src="<?php echo e($basePath); ?>assets/js/app.js"></script>
</body>
</html>
