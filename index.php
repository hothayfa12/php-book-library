<?php
session_start();
//120230430
/*
|--------------------------------------------------------------------------
| Helper Functions
|--------------------------------------------------------------------------
*/

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8', false);
}

function cleanInput($value)
{
    return htmlspecialchars(trim((string)$value), ENT_QUOTES, 'UTF-8');
}

function generateNewId($books)
{
    $maxId = 0;

    foreach ($books as $book) {
        if ((int)$book["id"] > $maxId) {
            $maxId = (int)$book["id"];
        }
    }

    return $maxId + 1;
}

function findBookById($books, $id)
{
    foreach ($books as $book) {
        if ((int)$book["id"] === (int)$id) {
            return $book;
        }
    }

    return null;
}

function sortUrl($column, $currentSort, $currentDirection, $searchTerm)
{
    $newDirection = "asc";

    if ($currentSort === $column && $currentDirection === "asc") {
        $newDirection = "desc";
    }

    $params = [
        "sort" => $column,
        "direction" => $newDirection
    ];

    if (!empty($searchTerm)) {
        $params["search"] = $searchTerm;
    }

    return "index.php?" . http_build_query($params);
}

/*
|--------------------------------------------------------------------------
| Genres Array
|--------------------------------------------------------------------------
*/

$genres = [
    "Fiction",
    "Non-Fiction",
    "Science",
    "History",
    "Biography",
    "Technology"
];

/*
|--------------------------------------------------------------------------
| Default Books Array
|--------------------------------------------------------------------------
*/

$defaultBooks = [
    [
        "id" => 1,
        "title" => "Clean Code",
        "author" => "hothayfa zd",
        "genre" => "Technology",
        "year" => 2008,
        "pages" => 464,
        "image_url" => "https://images-na.ssl-images-amazon.com/images/I/41xShlnTZTL.jpg"
    ],
    [
        "id" => 2,
        "title" => "A Brief History of Time",
        "author" => "ahmmed dsh",
        "genre" => "Science",
        "year" => 1988,
        "pages" => 256,
        "image_url" => ""
    ],
    [
        "id" => 3,
        "title" => "Steve Jobs",
        "author" => "omar fq",
        "genre" => "Biography",
        "year" => 2011,
        "pages" => 656,
        "image_url" => "https://images-na.ssl-images-amazon.com/images/I/41n1edvVlLL.jpg"
    ]
];

/*
|--------------------------------------------------------------------------
| Store Books in Session
|--------------------------------------------------------------------------
| Since this assignment does not use a database, session is used to keep
| added, updated, and deleted books during the current browser session.
*/

if (!isset($_SESSION["books"])) {
    $_SESSION["books"] = $defaultBooks;
}

$books = &$_SESSION["books"];

/*
|--------------------------------------------------------------------------
| Initial Variables
|--------------------------------------------------------------------------
*/

$errors = [];

$submittedData = [
    "title" => "",
    "author" => "",
    "genre" => "",
    "year" => "",
    "pages" => "",
    "image_url" => ""
];

$isEditMode = false;
$editId = null;

/*
|--------------------------------------------------------------------------
| Handle Delete Request
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_id"])) {
    $deleteId = (int)$_POST["delete_id"];

    $books = array_filter($books, function ($book) use ($deleteId) {
        return (int)$book["id"] !== $deleteId;
    });

    $books = array_values($books);

    $_SESSION["success"] = "Book deleted successfully.";

    header("Location: index.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Handle Add / Update Request
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST" && !isset($_POST["delete_id"])) {
    $updateId = isset($_POST["update_id"]) && $_POST["update_id"] !== ""
        ? (int)$_POST["update_id"]
        : null;

    $title = cleanInput($_POST["title"] ?? "");
    $author = cleanInput($_POST["author"] ?? "");
    $genre = cleanInput($_POST["genre"] ?? "");
    $year = cleanInput($_POST["year"] ?? "");
    $pages = cleanInput($_POST["pages"] ?? "");
    $imageUrl = cleanInput($_POST["image_url"] ?? "");

    $submittedData = [
        "title" => $title,
        "author" => $author,
        "genre" => $genre,
        "year" => $year,
        "pages" => $pages,
        "image_url" => $imageUrl
    ];

    /*
    |--------------------------------------------------------------------------
    | Validation Rules
    |--------------------------------------------------------------------------
    */

    if ($title === "") {
        $errors["title"] = "Title is required.";
    } elseif (strlen($title) < 3 || strlen($title) > 120) {
        $errors["title"] = "Title must be between 3 and 120 characters.";
    }

    if ($author === "") {
        $errors["author"] = "Author is required.";
    } else {
        $authorWords = preg_split('/\s+/', trim($author), -1, PREG_SPLIT_NO_EMPTY);

        if (count($authorWords) < 2) {
            $errors["author"] = "Author must contain at least two words.";
        }
    }

    if ($genre === "") {
        $errors["genre"] = "Genre is required.";
    } elseif (!in_array($genre, $genres)) {
        $errors["genre"] = "Invalid genre selected.";
    }

    $currentYear = (int)date("Y");

    if ($year === "") {
        $errors["year"] = "Year is required.";
    } elseif (!ctype_digit($year) || strlen($year) !== 4) {
        $errors["year"] = "Year must be a 4-digit integer.";
    } elseif ((int)$year < 1000 || (int)$year > $currentYear) {
        $errors["year"] = "Year must be between 1000 and {$currentYear}.";
    }

    if ($pages === "") {
        $errors["pages"] = "Pages is required.";
    } elseif (!ctype_digit($pages) || (int)$pages <= 0) {
        $errors["pages"] = "Pages must be a positive integer greater than 0.";
    }

    if ($imageUrl !== "") {
        $imagePath = parse_url($imageUrl, PHP_URL_PATH);

        if (!preg_match('/\.(jpg|jpeg|png|gif)$/i', $imagePath)) {
            $errors["image_url"] = "Cover image URL must end with .jpg, .jpeg, .png, or .gif.";
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Add or Update Book if Validation Passes
    |--------------------------------------------------------------------------
    */

    if (empty($errors)) {
        if ($updateId !== null) {
            foreach ($books as $index => $book) {
                if ((int)$book["id"] === $updateId) {
                    $books[$index] = [
                        "id" => $updateId,
                        "title" => $title,
                        "author" => $author,
                        "genre" => $genre,
                        "year" => (int)$year,
                        "pages" => (int)$pages,
                        "image_url" => $imageUrl
                    ];

                    break;
                }
            }

            $_SESSION["success"] = "Book updated successfully.";
        } else {
            $newBook = [
                "id" => generateNewId($books),
                "title" => $title,
                "author" => $author,
                "genre" => $genre,
                "year" => (int)$year,
                "pages" => (int)$pages,
                "image_url" => $imageUrl
            ];

            $books[] = $newBook;

            $_SESSION["success"] = "Book added successfully.";
        }

        $submittedData = [
            "title" => "",
            "author" => "",
            "genre" => "",
            "year" => "",
            "pages" => "",
            "image_url" => ""
        ];

        header("Location: index.php");
        exit;
    }

    if ($updateId !== null) {
        $isEditMode = true;
        $editId = $updateId;
    }
}

/*
|--------------------------------------------------------------------------
| Detect Edit Mode by Query String
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] !== "POST" && isset($_GET["edit_id"])) {
    $editId = (int)$_GET["edit_id"];
    $bookToEdit = findBookById($books, $editId);

    if ($bookToEdit !== null) {
        $isEditMode = true;

        $submittedData = [
            "title" => $bookToEdit["title"],
            "author" => $bookToEdit["author"],
            "genre" => $bookToEdit["genre"],
            "year" => $bookToEdit["year"],
            "pages" => $bookToEdit["pages"],
            "image_url" => $bookToEdit["image_url"] ?? ""
        ];
    }
}

/*
|--------------------------------------------------------------------------
| Success Message
|--------------------------------------------------------------------------
*/

$successMessage = $_SESSION["success"] ?? "";

if (isset($_SESSION["success"])) {
    unset($_SESSION["success"]);
}

/*
|--------------------------------------------------------------------------
| Search and Sort
|--------------------------------------------------------------------------
*/

$searchTerm = cleanInput($_GET["search"] ?? "");
$displayBooks = $books;

if ($searchTerm !== "") {
    $filteredBooks = [];

    foreach ($displayBooks as $book) {
        if (
            stripos($book["title"], $searchTerm) !== false ||
            stripos($book["author"], $searchTerm) !== false
        ) {
            $filteredBooks[] = $book;
        }
    }

    $displayBooks = $filteredBooks;
}

$allowedSortColumns = ["id", "title", "author", "genre", "year", "pages"];
$sortBy = cleanInput($_GET["sort"] ?? "");
$direction = cleanInput($_GET["direction"] ?? "asc");

if (!in_array($direction, ["asc", "desc"])) {
    $direction = "asc";
}

if (in_array($sortBy, $allowedSortColumns)) {
    usort($displayBooks, function ($a, $b) use ($sortBy, $direction) {
        if (is_numeric($a[$sortBy]) && is_numeric($b[$sortBy])) {
            $result = $a[$sortBy] <=> $b[$sortBy];
        } else {
            $result = strcasecmp($a[$sortBy], $b[$sortBy]);
        }

        return $direction === "asc" ? $result : -$result;
    });
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PHP Book Library</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap 5 CSS CDN -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
</head>

<body class="bg-light">

<div class="container my-5">

    <div class="text-center mb-4">
        <h1 class="fw-bold">Personal Book Library</h1>
        <p class="text-muted">Manage your collection of books.</p>
    </div>

    <?php if ($successMessage !== ""): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= e($successMessage) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">

        <!-- Form Section -->
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <?= $isEditMode ? "Edit Book" : "Add New Book" ?>
                </div>

                <div class="card-body">

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            Please fix the errors below.
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="index.php">

                        <?php if ($isEditMode): ?>
                            <input type="hidden" name="update_id" value="<?= e($editId) ?>">
                        <?php endif; ?>

                        <div class="mb-3">
                            <label for="title" class="form-label">Title</label>
                            <input
                                type="text"
                                id="title"
                                name="title"
                                class="form-control <?= isset($errors["title"]) ? "is-invalid" : "" ?>"
                                value="<?= e($submittedData["title"] ?? "") ?>"
                            >
                            <?php if (isset($errors["title"])): ?>
                                <div class="invalid-feedback">
                                    <?= e($errors["title"]) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="author" class="form-label">Author</label>
                            <input
                                type="text"
                                id="author"
                                name="author"
                                class="form-control <?= isset($errors["author"]) ? "is-invalid" : "" ?>"
                                value="<?= e($submittedData["author"] ?? "") ?>"
                            >
                            <?php if (isset($errors["author"])): ?>
                                <div class="invalid-feedback">
                                    <?= e($errors["author"]) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="genre" class="form-label">Genre</label>
                            <select
                                id="genre"
                                name="genre"
                                class="form-select <?= isset($errors["genre"]) ? "is-invalid" : "" ?>"
                            >
                                <option value="">Select Genre</option>

                                <?php foreach ($genres as $genre): ?>
                                    <option
                                        value="<?= e($genre) ?>"
                                        <?= (($submittedData["genre"] ?? "") === $genre) ? "selected" : "" ?>
                                    >
                                        <?= e($genre) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <?php if (isset($errors["genre"])): ?>
                                <div class="invalid-feedback">
                                    <?= e($errors["genre"]) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="year" class="form-label">Year</label>
                            <input
                                type="text"
                                id="year"
                                name="year"
                                class="form-control <?= isset($errors["year"]) ? "is-invalid" : "" ?>"
                                value="<?= e($submittedData["year"] ?? "") ?>"
                            >
                            <?php if (isset($errors["year"])): ?>
                                <div class="invalid-feedback">
                                    <?= e($errors["year"]) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="pages" class="form-label">Pages</label>
                            <input
                                type="text"
                                id="pages"
                                name="pages"
                                class="form-control <?= isset($errors["pages"]) ? "is-invalid" : "" ?>"
                                value="<?= e($submittedData["pages"] ?? "") ?>"
                            >
                            <?php if (isset($errors["pages"])): ?>
                                <div class="invalid-feedback">
                                    <?= e($errors["pages"]) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="image_url" class="form-label">Cover Image URL <span class="text-muted">(Optional)</span></label>
                            <input
                                type="text"
                                id="image_url"
                                name="image_url"
                                class="form-control <?= isset($errors["image_url"]) ? "is-invalid" : "" ?>"
                                value="<?= e($submittedData["image_url"] ?? "") ?>"
                                placeholder="https://example.com/image.jpg"
                            >
                            <?php if (isset($errors["image_url"])): ?>
                                <div class="invalid-feedback">
                                    <?= e($errors["image_url"]) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <?= $isEditMode ? "Update Book" : "Add Book" ?>
                            </button>

                            <?php if ($isEditMode): ?>
                                <a href="index.php" class="btn btn-secondary">
                                    Cancel Edit
                                </a>
                            <?php endif; ?>
                        </div>

                    </form>
                </div>
            </div>
        </div>

        <!-- Table Section -->
        <div class="col-md-8">

            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white">
                    Book List
                </div>

                <div class="card-body">

                    <!-- Search Form -->
                    <form method="GET" action="index.php" class="mb-3">
                        <div class="input-group">
                            <input
                                type="text"
                                name="search"
                                class="form-control"
                                placeholder="Search by title or author..."
                                value="<?= e($searchTerm) ?>"
                            >
                            <button class="btn btn-outline-primary" type="submit">
                                Search
                            </button>

                            <?php if ($searchTerm !== ""): ?>
                                <a href="index.php" class="btn btn-outline-secondary">
                                    Clear
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-bordered align-middle">
                            <thead class="table-dark">
                            <tr>
                                <th>
                                    <a class="text-white text-decoration-none" href="<?= e(sortUrl("id", $sortBy, $direction, $searchTerm)) ?>">
                                        #
                                    </a>
                                </th>
                                <th>Cover</th>
                                <th>
                                    <a class="text-white text-decoration-none" href="<?= e(sortUrl("title", $sortBy, $direction, $searchTerm)) ?>">
                                        Title
                                    </a>
                                </th>
                                <th>
                                    <a class="text-white text-decoration-none" href="<?= e(sortUrl("author", $sortBy, $direction, $searchTerm)) ?>">
                                        Author
                                    </a>
                                </th>
                                <th>
                                    <a class="text-white text-decoration-none" href="<?= e(sortUrl("genre", $sortBy, $direction, $searchTerm)) ?>">
                                        Genre
                                    </a>
                                </th>
                                <th>
                                    <a class="text-white text-decoration-none" href="<?= e(sortUrl("year", $sortBy, $direction, $searchTerm)) ?>">
                                        Year
                                    </a>
                                </th>
                                <th>
                                    <a class="text-white text-decoration-none" href="<?= e(sortUrl("pages", $sortBy, $direction, $searchTerm)) ?>">
                                        Pages
                                    </a>
                                </th>
                                <th>Actions</th>
                            </tr>
                            </thead>

                            <tbody>
                            <?php if (empty($displayBooks)): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted">
                                        No books found.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($displayBooks as $book): ?>
                                    <tr>
                                        <td><?= e($book["id"]) ?></td>

                                        <td>
                                            <?php if (!empty($book["image_url"])): ?>
                                                <img
                                                    src="<?= e($book["image_url"]) ?>"
                                                    alt="Book cover"
                                                    class="img-thumbnail"
                                                    style="width: 55px; height: 75px; object-fit: cover;"
                                                >
                                            <?php else: ?>
                                                <span class="text-muted">No image</span>
                                            <?php endif; ?>
                                        </td>

                                        <td><?= e($book["title"]) ?></td>
                                        <td><?= e($book["author"]) ?></td>
                                        <td><?= e($book["genre"]) ?></td>
                                        <td><?= e($book["year"]) ?></td>
                                        <td><?= e($book["pages"]) ?></td>

                                        <td>
                                            <a
                                                href="index.php?edit_id=<?= e($book["id"]) ?>"
                                                class="btn btn-sm btn-warning mb-1"
                                            >
                                                Edit
                                            </a>

                                            <button
                                                type="button"
                                                class="btn btn-sm btn-danger mb-1"
                                                data-bs-toggle="modal"
                                                data-bs-target="#deleteModal<?= e($book["id"]) ?>"
                                            >
                                                Delete
                                            </button>
                                        </td>
                                    </tr>

                                    <!-- Delete Confirmation Modal -->
                                    <div
                                        class="modal fade"
                                        id="deleteModal<?= e($book["id"]) ?>"
                                        tabindex="-1"
                                        aria-labelledby="deleteModalLabel<?= e($book["id"]) ?>"
                                        aria-hidden="true"
                                    >
                                        <div class="modal-dialog">
                                            <div class="modal-content">

                                                <div class="modal-header">
                                                    <h5
                                                        class="modal-title"
                                                        id="deleteModalLabel<?= e($book["id"]) ?>"
                                                    >
                                                        Confirm Delete
                                                    </h5>

                                                    <button
                                                        type="button"
                                                        class="btn-close"
                                                        data-bs-dismiss="modal"
                                                        aria-label="Close"
                                                    ></button>
                                                </div>

                                                <div class="modal-body">
                                                    Are you sure you want to delete
                                                    <strong><?= e($book["title"]) ?></strong>?
                                                </div>

                                                <div class="modal-footer">
                                                    <button
                                                        type="button"
                                                        class="btn btn-secondary"
                                                        data-bs-dismiss="modal"
                                                    >
                                                        Cancel
                                                    </button>

                                                    <form method="POST" action="index.php">
                                                        <input
                                                            type="hidden"
                                                            name="delete_id"
                                                            value="<?= e($book["id"]) ?>"
                                                        >

                                                        <button type="submit" class="btn btn-danger">
                                                            Yes, Delete
                                                        </button>
                                                    </form>
                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($sortBy !== ""): ?>
                        <p class="text-muted small">
                            Sorted by: <strong><?= e($sortBy) ?></strong>
                            / Direction: <strong><?= e($direction) ?></strong>
                        </p>
                    <?php endif; ?>

                </div>
            </div>
        </div>

    </div>
</div>

<!-- Bootstrap 5 JS CDN -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>