<?php
session_start();
if (!isset($_SESSION['usernameUser'])) {
    header("Location: ../login.php");
    exit;
}

require '../functions.php';
$username_user = $_SESSION["usernameUser"];
$query_nama_user = mysqli_query($con, "SELECT * FROM users WHERE username='$username_user'");
$dataUser = mysqli_fetch_array($query_nama_user);
$idUser = $dataUser["user_id"];

// Ambil semua genre yang tersedia untuk filter
$genres = Query("SELECT DISTINCT genre FROM genres");

// Handle search, filter, dan sorting
$search = isset($_GET['search']) ? $_GET['search'] : '';
$genre_filter = isset($_GET['genre']) ? $_GET['genre'] : '';
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'nama_game';
$sort_order = isset($_GET['sort_order']) ? $_GET['sort_order'] : 'ASC';

// Query untuk mengambil game yang sudah dibeli dengan filter dan sorting
$query = "SELECT DISTINCT g.* FROM games g 
          JOIN pembelian p ON g.game_id = p.game_id";

// Tambahkan JOIN untuk genres jika ada filter genre
if (!empty($genre_filter)) {
    $query .= " JOIN genres gr ON g.game_id = gr.game_id";
}

// Mulai WHERE clause
$query .= " WHERE p.user_id = '$idUser'";

// Tambahkan kondisi pencarian
if (!empty($search)) {
    $query .= " AND g.nama_game LIKE '%$search%'";
}

// Tambahkan kondisi filter genre
if (!empty($genre_filter)) {
    $query .= " AND gr.genre = '$genre_filter'";
}

// Tambahkan sorting
$valid_sort_columns = ['nama_game', 'harga'];
$valid_sort_orders = ['ASC', 'DESC'];

if (in_array($sort_by, $valid_sort_columns) && in_array($sort_order, $valid_sort_orders)) {
    $query .= " ORDER BY g.$sort_by $sort_order";
} else {
    $query .= " ORDER BY g.nama_game ASC"; // default sorting
}

$games = Query($query);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Gameery - Library Game</title>
    <link rel="icon" href="../assets/icons/logo.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="../css/style.css">

    <style>
        body {
            background: linear-gradient(180deg, #f0f5ff 0%, #162f65 100%);
            color: #eee;
            margin: 0;
            padding-top: 56px;
            min-height: 100vh;
        }

        .navbar-custom {
            background-color: #162f65 !important;
        }

        .navbar-custom .navbar-brand,
        .navbar-custom .nav-link {
            color: #fff;
            font-weight: 600;
        }

        .navbar-custom .nav-link:hover,
        .navbar-custom .nav-link.active {
            color: #a8c0ff;
        }

        .container-content {
            margin-top: 1.5rem;
            padding: 1.5rem;
            background: linear-gradient(180deg, #f0f5ff 0%, #162f65 100%);
            border-radius: 8px;
            min-height: calc(100vh - 56px - 3rem);
        }

        .card-item {
            background-color: #1e1e1e;
            border-radius: 10px;
            border: none;
            overflow: hidden;
            transition: box-shadow 0.3s ease;
            cursor: default;
            height: 100%;
            display: flex;
            flex-direction: column;
            color: #eee;
        }

        .card-item:hover {
            box-shadow: 0 0 18px rgb(29, 112, 255);
        }

        .card-item img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 10px 10px 0 0;
            border-bottom: 1px solid #333;
        }

        .card-body-item {
            padding: 1rem;
            flex-grow: 1;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem; /* Memberikan jarak antara konten kiri dan kanan */
        }

        .card-details {
            display: flex;
            flex-direction: column;
            gap: 5px;
            flex: 1; /* Mengambil sisa ruang yang tersedia */
            min-width: 0; /* Memungkinkan text overflow bekerja */
        }

        .item-name {
            font-weight: 600;
            font-size: 1.1rem;
            word-wrap: break-word;
            overflow-wrap: break-word;
            hyphens: auto;
            line-height: 1.2;
        }

        .game-price {
            color: #28a745;
            font-weight: bold;
            font-size: 0.9rem;
            white-space: nowrap; /* Mencegah harga terpotong ke baris baru */
        }

        .action-buttons {
            display: flex;
            gap: 0.4rem;
            flex-shrink: 0; /* Mencegah tombol menyusut */
            align-items: center;
        }

        .btn-action {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            font-size: 0.875rem;
        }

        .btn-add {
            background-color: #198754;
            color: #fff;
            font-weight: 600;
            border-radius: 25px;
            padding: 0.5rem 1.5rem;
            border: none;
            text-decoration: none;
            transition: background-color 0.3s ease;
        }

        .btn-add:hover {
            background-color: #157347;
            color: #fff;
            text-decoration: none;
        }

        .welcome-text {
            color: #0b2361;
            font-weight: 700;
            font-size: 2rem;
        }

        .image-game-modal {
            width: 100%;
            height: auto;
            max-width: 600px;
            object-fit: contain;
        }

        .btn-success .spinner-border {
            vertical-align: middle;
            margin-right: 5px;
        }

        .search-container {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .search-input {
            flex-grow: 1;
            min-width: 200px;
            padding: 10px 15px;
            border-radius: 25px;
            border: 1px solid #ced4da;
            outline: none;
        }

        .filter-select {
            padding: 10px 15px;
            border-radius: 25px;
            border: 1px solid #ced4da;
            background-color: white;
            cursor: pointer;
            min-width: 120px;
        }

        .search-button {
            padding: 10px 20px;
            border-radius: 25px;
            background-color: #162f65;
            color: white;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .search-button:hover {
            background-color: #1a3a7a;
        }

        .sort-controls {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        @media (max-width: 768px) {
            .search-container {
                flex-direction: column;
            }
            .search-input {
                min-width: auto;
            }
            .sort-controls {
                flex-direction: column;
                align-items: stretch;
                gap: 5px;
            }
            .card-body-item {
                flex-direction: column;
                align-items: stretch;
                gap: 1rem;
            }
            .action-buttons {
                justify-content: center;
            }
        }
        @media (max-width: 576px) {
            .item-name {
                font-size: 1rem;
            }
            .btn-action {
                width: 28px;
                height: 28px;
                font-size: 0.75rem;
            }
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-custom fixed-top">
        <div class="container">
            <h1 class="navbar-brand d-flex align-items-center">
                <img src="../assets/icons/logo.png" alt="Logo" class="logo" style="width: 40px; height: 40px; border-radius: 50%; margin-right: 10px;">
                Gameery
            </h1>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent"
                aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon" style="filter: invert(1);"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarContent">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link me-2 active" href="library_game.php">Library Game</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-danger" href="../logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container container-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="welcome-text">Welcome <?php echo $dataUser['nama_pengguna']; ?>! </h2>
        </div>

        <!-- Search, Filter, dan Sort Section -->
        <form method="GET" action="">
            <div class="search-container">
                <input type="text" name="search" class="search-input" placeholder="Search game..." value="<?php echo htmlspecialchars($search); ?>" autocomplete="off">
                
                <select name="genre" class="filter-select">
                    <option value="">All Genres</option>
                    <?php foreach ($genres as $genre): ?>
                        <option value="<?php echo $genre['genre']; ?>" <?php echo ($genre_filter == $genre['genre']) ? 'selected' : ''; ?>>
                            <?php echo $genre['genre']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <div class="sort-controls">
                    <select name="sort_by" class="filter-select">
                        <option value="nama_game" <?php echo ($sort_by == 'nama_game') ? 'selected' : ''; ?>>Sort by Name</option>
                        <option value="harga" <?php echo ($sort_by == 'harga') ? 'selected' : ''; ?>>Sort by Price</option>
                    </select>
                    
                    <select name="sort_order" class="filter-select">
                        <option value="ASC" <?php echo ($sort_order == 'ASC') ? 'selected' : ''; ?>>Ascending</option>
                        <option value="DESC" <?php echo ($sort_order == 'DESC') ? 'selected' : ''; ?>>Descending</option>
                    </select>
                </div>

                <button type="submit" class="search-button">
                    <i class="fas fa-search"></i> Search
                </button>
            </div>
        </form>

        <div class="row g-4">
            <?php if (empty($games)): ?>
                <div class="col-12 text-center py-5">
                    <h4 class="text-muted">Belum ada game di library Anda</h4>
                </div>
            <?php else: ?>
                <!-- Data Game Start -->
                <?php foreach ($games as $row) : ?>
                    <div class="col-md-4 col-sm-6">
                        <div class="card-item">
                            <img src="../assets/image/<?php echo $row["gambar"]; ?>" alt="<?php echo htmlspecialchars($row["nama_game"]); ?>" />
                            <div class="card-body-item">
                                <div class="card-details">
                                    <div class="item-name"><?php echo htmlspecialchars($row["nama_game"]); ?></div>
                                    <?php if(isset($row["harga"])): ?>
                                        <div class="game-price">Rp <?php echo number_format($row["harga"], 0, ',', '.'); ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="action-buttons">
                                    <a href="view_game.php?id=<?php echo $row['game_id']; ?>" class="btn btn-outline-primary btn-sm btn-action" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <!-- Tombol Download (selalu tampil karena ini library game yang sudah dibeli) -->
                                    <button class="btn btn-outline-info btn-sm btn-action download-btn"
                                        title="Download Game"
                                        data-game-id="<?php echo $row['game_id']; ?>"
                                        data-game-name="<?php echo htmlspecialchars($row['nama_game']); ?>">
                                        <i class="fa-solid fa-download"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <!-- Data Game End -->
            <?php endif; ?>
        </div>
    </div>

    <!-- Alert download -->
    <div id="customAlert" style="
    display: none;
    position: fixed;
    top: 20%;
    left: 50%;
    transform: translateX(-50%);
    background-color: #333;
    color: #fff;
    padding: 20px 30px;
    border-radius: 10px;
    font-size: 18px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.3);
    z-index: 9999;
    text-align: center;
    white-space: pre-line;">
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Handle tombol download (dengan pop-up animasi & auto refresh)
            document.querySelectorAll('.download-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const gameName = this.getAttribute('data-game-name');
                    const alertBox = document.getElementById('customAlert');

                    // Tampilkan pesan pertama
                    alertBox.textContent = `Memulai download game: ${gameName}\nSilakan tunggu...`;
                    alertBox.style.display = 'block';

                    // Setelah 2 detik, ubah ke pesan sukses
                    setTimeout(() => {
                        alertBox.textContent = `Game berhasil didownload!`;
                    }, 2000);

                    // Setelah total 3 detik, sembunyikan dan reload halaman
                    setTimeout(() => {
                        alertBox.style.display = 'none';
                        window.location.reload();
                    }, 3000);
                });
            });
        });
    </script>
</body>

</html>