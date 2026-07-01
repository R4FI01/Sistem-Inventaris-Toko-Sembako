<div class="modal animated zoomIn" id="delete-modal" tabindex="-1" aria-labelledby="deleteProductLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center p-4">
                <i class="bi bi-exclamation-triangle text-warning" style="font-size: 2rem"></i>
                <h5 class="mt-3" id="deleteProductLabel">Hapus produk?</h5>
                <p class="mb-0 text-muted">Produk akan dihapus secara permanen. Produk yang sudah memiliki riwayat transaksi atau pergerakan stok tidak dapat dihapus.</p>
                <input class="d-none" id="deleteID"/>
                <input class="d-none" id="deleteFilePath"/>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" id="delete-modal-close" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button onclick="itemDelete()" type="button" id="confirmDelete" class="btn bg-gradient-danger">Hapus Produk</button>
            </div>
        </div>
    </div>
</div>

<script>
    async function itemDelete() {
        const id = document.getElementById('deleteID').value;
        const deleteFilePath = document.getElementById('deleteFilePath').value;

        if (!id) return;

        document.getElementById('delete-modal-close').click();

        try {
            showLoader();
            const res = await axios.post('/delete-product', {id: id, file_path: deleteFilePath});

            if (res.data === 1 || res.data === '1' || res.data?.status === 'success') {
                successToast('Produk berhasil dihapus.');
                await getList();
            } else {
                errorToast(res.data?.message || 'Produk tidak dapat dihapus.');
            }
        } catch (error) {
            errorToast(error.response?.data?.message || 'Produk tidak dapat dihapus.');
        } finally {
            hideLoader();
        }
    }
</script>
