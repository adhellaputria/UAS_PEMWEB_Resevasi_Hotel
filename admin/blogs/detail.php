<?php
require_once '../../config/database.php';
requireLogin();
requireAdmin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$result = mysqli_query($conn, "
    SELECT b.*, u.name AS author_name
    FROM blogs b
    LEFT JOIN users u ON b.author_id = u.id
    WHERE b.id = $id
");

$blog = mysqli_fetch_assoc($result);

if (!$blog) {
    header("Location: " . BASE_URL . "/admin/blogs/manage.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Detail Artikel - <?= htmlspecialchars($blog['title']) ?></title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
body{
    background:#f8fafc;
    font-family:'Inter',sans-serif;
}

.main-content{
    margin-left:200px;
    padding:2rem 2.5rem;
}
@media(max-width:767.98px){
    .main-content{
        margin-left:0;
        padding:1rem;
        padding-top:5rem;
    }
}

/* header */
.page-header{
    margin-bottom:2rem;
    animation:fadeInDown .6s ease-out;
}
.page-title{
    font-size:2rem;
    font-weight:800;
    color:#0f172a;
    display:flex;
    align-items:center;
    gap:12px;
}
.page-title i{
    color:#facc15;
}

/* card */
.detail-card{
    background:#fff;
    border-radius:16px;
    padding:2rem;
    box-shadow:0 4px 20px rgba(0,0,0,.06);
    animation:fadeInUp .6s ease-out .1s backwards;
}

/* meta */
.blog-meta{
    font-size:.85rem;
    color:#64748b;
    margin-bottom:1rem;
}
.blog-meta span{
    margin-right:1rem;
}

/* category badge */
.category-badge{
    display:inline-block;
    padding:.35rem .75rem;
    border-radius:8px;
    font-size:.75rem;
    font-weight:600;
}
.category-hotel{background:#fef3c7;color:#92400e;}
.category-service{background:#dbeafe;color:#1e40af;}
.category-facility{background:#dcfce7;color:#166534;}
.category-tips{background:#f3e8ff;color:#6b21a8;}

/* image */
.blog-image{
    width:100%;
    max-height:380px;
    object-fit:cover;
    border-radius:12px;
    margin:1.5rem 0;
}

/* content */
.blog-content{
    color:#334155;
    line-height:1.8;
    font-size:.95rem;
    white-space:pre-line;
}

/* anim */
@keyframes fadeInDown{
    from{opacity:0;transform:translateY(-30px)}
    to{opacity:1;transform:translateY(0)}
}
@keyframes fadeInUp{
    from{opacity:0;transform:translateY(30px)}
    to{opacity:1;transform:translateY(0)}
}

.btn-back {
    background: #f1f5f9;
    border: none;
    color: #475569;
    font-weight: 600;
    padding: 0.75rem 1.5rem;
    border-radius: 12px;
    transition: all 0.25s ease;
    text-decoration: none;
    display: inline-block;
}

.btn-back:hover {
    background: #e2e8f0;
    color: #334155;
}
</style>
</head>

<body>
<?php include_once '../../includes/sidebar.php'; ?>

<div class="main-content">

    <div class="page-header">
        <h1 class="page-title">
            <i class="fa-solid fa-newspaper"></i>
            Detail Artikel
        </h1>
    </div>

    <div class="detail-card">

        <h2 class="fw-bold mb-2"><?= htmlspecialchars($blog['title']) ?></h2>

        <div class="blog-meta">
            <span>
                <i class="fa-solid fa-tag"></i>
                <span class="category-badge category-<?= $blog['category'] ?>">
                    <?= ucfirst($blog['category']) ?>
                </span>
            </span>
            <span>
                <i class="fa-solid fa-user"></i>
                <?= htmlspecialchars($blog['author_name'] ?? 'Admin') ?>
            </span>
            <span>
                <i class="fa-solid fa-calendar"></i>
                <?= date('d M Y', strtotime($blog['created_at'])) ?>
            </span>
            <span>
                <i class="fa-solid fa-eye"></i>
                <?= number_format($blog['views']) ?> views
            </span>
        </div>

        <?php if (!empty($blog['image'])): ?>
            <img src="<?= BASE_URL . '/' . $blog['image'] ?>"
                 class="blog-image"
                 alt="<?= htmlspecialchars($blog['title']) ?>">
        <?php endif; ?>

        <div class="blog-content">
            <?= nl2br(htmlspecialchars($blog['content'])) ?>
        </div>

        <div class="mt-4">
            <a href="<?= BASE_URL ?>/admin/blogs/manage.php" class="btn btn-back">
                <i class="fa-solid fa-arrow-left"></i> Kembali
            </a>
        </div>

    </div>

</div>

</body>
</html>