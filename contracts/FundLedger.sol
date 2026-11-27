// SPDX-License-Identifier: MIT
pragma solidity ^0.8.20;

/// @title FundLedger - Ledger kas organisasi yang immutable
/// @notice Mencatat pemasukan (cash in) dan pengeluaran (cash out) dengan alur persetujuan Ketua
/// @author Intan Mutia (Jobdesk 3: Smart Contract Developer)
contract FundLedger {

    // =========================================================
    // 1. STRUKTUR DATA & VARIABEL ON-CHAIN
    // =========================================================

    // Jenis transaksi: cash_in / cash_out
    enum TransactionType { CashIn, CashOut }

    // Status transaksi: pending, completed, rejected
    enum Status { Pending, Completed, Rejected }

    // Struct data transaksi
    struct TransactionRecord {
        string id;               // ID transaksi (dari Laravel, mis. "TRX-001")
        uint256 amount;          // Nominal transaksi
        TransactionType txType;  // cash_in / cash_out
        string category;         // Kategori transaksi (mis. "Iuran", "Konsumsi")
        uint256 timestamp;       // Waktu pencatatan (block.timestamp)
        address maker;           // Address pembuat transaksi
        address checker;         // Address pemberi persetujuan (Ketua)
        Status status;           // pending / completed / rejected
    }

    // Mapping riwayat transaksi: id => TransactionRecord
    mapping(string => TransactionRecord) public transactions;

    // Daftar semua ID transaksi (agar riwayat bisa dibaca berurutan oleh Laravel)
    string[] public transactionIds;

    // Total saldo organisasi
    uint256 public totalBalance;

    // Address Ketua (satu-satunya yang boleh approve cash out)
    address public checkerAddress;

    // =========================================================
    // 2. EVENTS (agar Laravel bisa melacak transaksi yang masuk block)
    // =========================================================

    event CashInRecorded(
        string indexed idHash,
        string id,
        uint256 amount,
        string category,
        address indexed maker,
        uint256 timestamp
    );

    event CashOutRequested(
        string indexed idHash,
        string id,
        uint256 amount,
        string category,
        address indexed maker,
        uint256 timestamp
    );

    event CashOutApproved(
        string indexed idHash,
        string id,
        uint256 amount,
        address indexed checker,
        uint256 newBalance,
        uint256 timestamp
    );

    // Event tambahan (bonus) untuk penolakan
    event CashOutRejected(
        string indexed idHash,
        string id,
        address indexed checker,
        uint256 timestamp
    );

    // =========================================================
    // 3. MODIFIER
    // =========================================================

    // Hanya address Ketua yang boleh memanggil fungsi tertentu
    modifier onlyChecker() {
        require(msg.sender == checkerAddress, "Hanya Ketua (checker) yang diizinkan");
        _;
    }

    // =========================================================
    // CONSTRUCTOR
    // =========================================================

    // Address yang men-deploy contract otomatis menjadi Ketua (checker).
    // Bisa diganti nanti lewat changeChecker().
    constructor() {
        checkerAddress = msg.sender;
    }

    // =========================================================
    // 4. FUNCTIONS (METHOD)
    // =========================================================

    /// @notice Mencatat pemasukan kas. Saldo langsung bertambah, status Completed.
    function recordCashIn(
        string memory _id,
        uint256 _amount,
        string memory _category
    ) public {
        require(bytes(_id).length > 0, "ID tidak boleh kosong");
        require(!_exists(_id), "ID transaksi sudah ada");
        require(_amount > 0, "Nominal harus lebih dari 0");

        // Simpan transaksi dengan status Completed
        transactions[_id] = TransactionRecord({
            id: _id,
            amount: _amount,
            txType: TransactionType.CashIn,
            category: _category,
            timestamp: block.timestamp,
            maker: msg.sender,
            checker: address(0),
            status: Status.Completed
        });
        transactionIds.push(_id);

        // Tambah saldo
        totalBalance += _amount;

        emit CashInRecorded(_id, _id, _amount, _category, msg.sender, block.timestamp);
    }

    /// @notice Mengajukan pengeluaran kas. Status Pending, saldo BELUM dipotong.
    function requestCashOut(
        string memory _id,
        uint256 _amount,
        string memory _category
    ) public {
        require(bytes(_id).length > 0, "ID tidak boleh kosong");
        require(!_exists(_id), "ID transaksi sudah ada");
        require(_amount > 0, "Nominal harus lebih dari 0");

        transactions[_id] = TransactionRecord({
            id: _id,
            amount: _amount,
            txType: TransactionType.CashOut,
            category: _category,
            timestamp: block.timestamp,
            maker: msg.sender,
            checker: address(0),
            status: Status.Pending
        });
        transactionIds.push(_id);

        emit CashOutRequested(_id, _id, _amount, _category, msg.sender, block.timestamp);
    }

    /// @notice Menyetujui pengeluaran: Pending -> Completed, saldo dipotong, catat address Ketua.
    function approveCashOut(string memory _txId) public onlyChecker {
        require(_exists(_txId), "Transaksi tidak ditemukan");

        TransactionRecord storage trx = transactions[_txId];

        require(trx.txType == TransactionType.CashOut, "Bukan transaksi cash out");
        require(trx.status == Status.Pending, "Transaksi bukan berstatus Pending");
        require(msg.sender != trx.maker, "Pelanggaran Maker-Checker: Tidak dapat menyetujui transaksi sendiri");
        require(totalBalance >= trx.amount, "Saldo organisasi tidak cukup");

        // Ubah status, potong saldo, catat pemberi persetujuan
        trx.status = Status.Completed;
        trx.checker = msg.sender;
        totalBalance -= trx.amount;

        emit CashOutApproved(_txId, _txId, trx.amount, msg.sender, totalBalance, block.timestamp);
    }

    /// @notice Menolak pengeluaran: Pending -> Rejected. Saldo tidak berubah.
    function rejectCashOut(string memory _txId) public onlyChecker {
        require(_exists(_txId), "Transaksi tidak ditemukan");

        TransactionRecord storage trx = transactions[_txId];

        require(trx.txType == TransactionType.CashOut, "Bukan transaksi cash out");
        require(trx.status == Status.Pending, "Transaksi bukan berstatus Pending");

        trx.status = Status.Rejected;
        trx.checker = msg.sender;

        emit CashOutRejected(_txId, _txId, msg.sender, block.timestamp);
    }

    /// @notice Fungsi view untuk membaca total saldo on-chain.
    function getOrganizationBalance() public view returns (uint256) {
        return totalBalance;
    }

    // =========================================================
    // FUNGSI PEMBANTU (opsional, memudahkan Laravel & testing)
    // =========================================================

    /// @notice Ambil satu transaksi lengkap berdasarkan ID.
    function getTransaction(string memory _id) public view returns (TransactionRecord memory) {
        require(_exists(_id), "Transaksi tidak ditemukan");
        return transactions[_id];
    }

    /// @notice Mengambil seluruh transaksi yang tercatat on-chain untuk riwayat lengkap di Remix / DApp.
    function getAllTransactions() external view returns (TransactionRecord[] memory) {
        TransactionRecord[] memory allRecords = new TransactionRecord[](transactionIds.length);
        for (uint256 i = 0; i < transactionIds.length; i++) {
            allRecords[i] = transactions[transactionIds[i]];
        }
        return allRecords;
    }

    /// @notice Mengambil seluruh ID transaksi dalam satu array.
    function getAllTransactionIds() external view returns (string[] memory) {
        return transactionIds;
    }

    /// @notice Mengambil daftar transaksi yang masih berstatus Pending (menunggu persetujuan Ketua).
    function getPendingTransactions() external view returns (TransactionRecord[] memory) {
        uint256 pendingCount = 0;
        for (uint256 i = 0; i < transactionIds.length; i++) {
            if (transactions[transactionIds[i]].status == Status.Pending) {
                pendingCount++;
            }
        }

        TransactionRecord[] memory pendingRecords = new TransactionRecord[](pendingCount);
        uint256 idx = 0;
        for (uint256 i = 0; i < transactionIds.length; i++) {
            if (transactions[transactionIds[i]].status == Status.Pending) {
                pendingRecords[idx] = transactions[transactionIds[i]];
                idx++;
            }
        }
        return pendingRecords;
    }

    /// @notice Mengambil transaksi berdasarkan urutan indeks (0 hingga N-1).
    function getTransactionByIndex(uint256 _index) external view returns (TransactionRecord memory) {
        require(_index < transactionIds.length, "Indeks transaksi di luar batas");
        return transactions[transactionIds[_index]];
    }

    /// @notice Ambil detail tuple transaksi yang mudah dibaca di panel Remix.
    function getTransactionDetails(string memory _id) external view returns (
        string memory id,
        uint256 amount,
        TransactionType txType,
        string memory category,
        uint256 timestamp,
        address maker,
        address checker,
        Status status
    ) {
        require(_exists(_id), "Transaksi tidak ditemukan");
        TransactionRecord memory trx = transactions[_id];
        return (
            trx.id,
            trx.amount,
            trx.txType,
            trx.category,
            trx.timestamp,
            trx.maker,
            trx.checker,
            trx.status
        );
    }

    /// @notice Jumlah seluruh transaksi yang tercatat.
    function getTransactionCount() public view returns (uint256) {
        return transactionIds.length;
    }

    /// @notice Ganti address Ketua (hanya Ketua saat ini).
    function changeChecker(address _newChecker) public onlyChecker {
        require(_newChecker != address(0), "Address tidak valid");
        checkerAddress = _newChecker;
    }

    // Cek apakah ID transaksi sudah pernah dicatat
    function _exists(string memory _id) internal view returns (bool) {
        return bytes(transactions[_id].id).length > 0;
    }
}
