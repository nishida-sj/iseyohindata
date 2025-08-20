<?php
ob_start();
?>

<div class="row">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= url('/') ?>">ホーム</a></li>
                <li class="breadcrumb-item active">ご用品申込</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h3 class="card-title mb-0">
                    <i class="fas fa-shopping-cart me-2"></i>ご用品申込フォーム
                </h3>
            </div>
            <div class="card-body">
                <!-- 申込フロー表示 -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="step-item active">
                                <div class="step-circle bg-primary text-white">1</div>
                                <div class="step-label">入力</div>
                            </div>
                            <div class="step-line"></div>
                            <div class="step-item">
                                <div class="step-circle bg-secondary text-white">2</div>
                                <div class="step-label">確認</div>
                            </div>
                            <div class="step-line"></div>
                            <div class="step-item">
                                <div class="step-circle bg-secondary text-white">3</div>
                                <div class="step-label">完了</div>
                            </div>
                        </div>
                    </div>
                </div>

                <form method="POST" action="<?= url('/order') ?>" id="orderForm">
                    <?= csrf_field() ?>
                    
                    <!-- 申込者情報 -->
                    <section class="mb-5">
                        <h4 class="border-bottom pb-2 mb-4">
                            <i class="fas fa-user me-2"></i>申込者情報
                        </h4>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="parent_name" class="form-label">保護者名 <span class="text-danger">*</span></label>
                                <input type="text" class="form-control <?= has_error('parent_name') ? 'is-invalid' : '' ?>" 
                                       id="parent_name" name="parent_name" 
                                       value="<?= e(old('parent_name')) ?>" 
                                       placeholder="山田 太郎" required>
                                <?php if (has_error('parent_name')): ?>
                                    <div class="invalid-feedback">
                                        <?= implode('<br>', error('parent_name')) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="child_name" class="form-label">入園児氏名 <span class="text-danger">*</span></label>
                                <input type="text" class="form-control <?= has_error('child_name') ? 'is-invalid' : '' ?>" 
                                       id="child_name" name="child_name" 
                                       value="<?= e(old('child_name')) ?>" 
                                       placeholder="山田 花子" required>
                                <?php if (has_error('child_name')): ?>
                                    <div class="invalid-feedback">
                                        <?= implode('<br>', error('child_name')) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="child_name_kana" class="form-label">フリガナ（全角カタカナ） <span class="text-danger">*</span></label>
                                <input type="text" class="form-control <?= has_error('child_name_kana') ? 'is-invalid' : '' ?>" 
                                       id="child_name_kana" name="child_name_kana" 
                                       value="<?= e(old('child_name_kana')) ?>" 
                                       placeholder="ヤマダ ハナコ" required>
                                <?php if (has_error('child_name_kana')): ?>
                                    <div class="invalid-feedback">
                                        <?= implode('<br>', error('child_name_kana')) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="age_group" class="form-label">入園年齢区分 <span class="text-danger">*</span></label>
                                <select class="form-select <?= has_error('age_group') ? 'is-invalid' : '' ?>" 
                                        id="age_group" name="age_group" required>
                                    <option value="">年齢区分を選択してください</option>
                                    <?php foreach ($age_groups as $age => $label): ?>
                                        <option value="<?= $age ?>" <?= old('age_group') == $age ? 'selected' : '' ?>>
                                            <?= e($label) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (has_error('age_group')): ?>
                                    <div class="invalid-feedback">
                                        <?= implode('<br>', error('age_group')) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </section>
                    
                    <!-- 商品選択 -->
                    <section class="mb-5">
                        <h4 class="border-bottom pb-2 mb-4">
                            <i class="fas fa-shopping-bag me-2"></i>商品選択
                        </h4>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            年齢区分を選択すると、対象の商品が表示されます。
                        </div>
                        
                        <div id="product-list" class="loading">
                            <div class="text-center p-4">
                                <div class="spinner-border" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="mt-2">商品を読み込み中...</p>
                            </div>
                        </div>
                    </section>
                    
                    <!-- 注文合計 -->
                    <section class="mb-4">
                        <div class="card bg-light">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h5>選択商品数: <span id="total-items" class="text-primary">0</span> 点</h5>
                                    </div>
                                    <div class="col-md-6 text-end">
                                        <h5>合計金額: <span id="total-amount" class="text-success">0</span> 円</h5>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                    
                    <!-- 送信ボタン -->
                    <div class="text-center">
                        <button type="submit" class="btn btn-primary btn-lg" id="submit-btn" disabled>
                            <i class="fas fa-arrow-right me-2"></i>確認画面へ進む
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- CSS -->
<style>
.step-item {
    text-align: center;
    flex: 1;
}

.step-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 5px;
    font-weight: bold;
}

.step-line {
    flex: 1;
    height: 2px;
    background-color: #dee2e6;
    margin: 20px 0;
}

.step-item.active .step-circle {
    background-color: var(--primary-color) !important;
}

.product-card {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
    transition: all 0.2s;
}

.product-card:hover {
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.product-card.selected {
    border-color: var(--primary-color);
    background-color: #f8f9ff;
}

.quantity-input {
    max-width: 80px;
}

.product-image {
    width: 100px;
    height: 80px;
    object-fit: cover;
    border-radius: 5px;
}

@media (max-width: 768px) {
    .step-line {
        display: none;
    }
    
    .product-image {
        width: 80px;
        height: 60px;
    }
}
</style>

<?php
$additional_js = '
<script>
document.addEventListener("DOMContentLoaded", function() {
    const ageGroupSelect = document.getElementById("age_group");
    const productList = document.getElementById("product-list");
    const totalItemsSpan = document.getElementById("total-items");
    const totalAmountSpan = document.getElementById("total-amount");
    const submitBtn = document.getElementById("submit-btn");
    
    let products = [];
    
    // 年齢区分変更時の処理
    ageGroupSelect.addEventListener("change", function() {
        const ageGroup = this.value;
        if (ageGroup) {
            loadProducts(ageGroup);
        } else {
            productList.innerHTML = `
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    年齢区分を選択してください。
                </div>
            `;
            updateTotal();
        }
    });
    
    // 商品読み込み
    function loadProducts(ageGroup) {
        showLoading();
        
        fetch(`/api/products/age/${ageGroup}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    products = data.products;
                    renderProducts();
                } else {
                    throw new Error(data.error || "商品の取得に失敗しました");
                }
            })
            .catch(error => {
                console.error("Error:", error);
                productList.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        商品の読み込みに失敗しました。ページを再読み込みしてお試しください。
                    </div>
                `;
            })
            .finally(() => {
                hideLoading();
            });
    }
    
    // 商品リスト表示
    function renderProducts() {
        if (products.length === 0) {
            productList.innerHTML = `
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    選択された年齢区分には商品が設定されていません。
                </div>
            `;
            return;
        }
        
        let html = "<div class=\"row\">";
        products.forEach(product => {
            const oldQuantity = getOldQuantity(product.id);
            html += `
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="product-card" data-product-id="${product.id}">
                        <div class="row align-items-center">
                            <div class="col-4">
                                <img src="${product.image_url}" alt="${product.name}" class="product-image">
                            </div>
                            <div class="col-8">
                                <h6 class="mb-1">${product.name}</h6>
                                <p class="text-muted small mb-1">${product.specification || ""}</p>
                                <p class="text-primary fw-bold mb-2">${product.formatted_price}</p>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">数量</span>
                                    <input type="number" class="form-control quantity-input" 
                                           name="quantity_${product.id}" 
                                           value="${oldQuantity}" 
                                           min="0" max="99" 
                                           data-product-id="${product.id}"
                                           data-price="${product.price}">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
        html += "</div>";
        
        productList.innerHTML = html;
        
        // 数量入力イベントリスナー追加
        const quantityInputs = productList.querySelectorAll(".quantity-input");
        quantityInputs.forEach(input => {
            input.addEventListener("input", updateTotal);
            input.addEventListener("change", updateTotal);
        });
        
        // 初期合計計算
        updateTotal();
    }
    
    // 旧入力値の取得
    function getOldQuantity(productId) {
        const oldInput = ' . json_encode($old_input ?? []) . ';
        return oldInput["quantity_" + productId] || 0;
    }
    
    // 合計計算・表示更新
    function updateTotal() {
        let totalItems = 0;
        let totalAmount = 0;
        
        const quantityInputs = document.querySelectorAll(".quantity-input");
        quantityInputs.forEach(input => {
            const quantity = parseInt(input.value) || 0;
            const price = parseInt(input.dataset.price) || 0;
            const productCard = input.closest(".product-card");
            
            if (quantity > 0) {
                totalItems += quantity;
                totalAmount += quantity * price;
                productCard.classList.add("selected");
            } else {
                productCard.classList.remove("selected");
            }
        });
        
        totalItemsSpan.textContent = totalItems;
        totalAmountSpan.textContent = formatPrice(totalAmount);
        
        // 送信ボタンの有効/無効
        submitBtn.disabled = totalItems === 0;
    }
    
    function showLoading() {
        productList.innerHTML = `
            <div class="text-center p-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">商品を読み込み中...</p>
            </div>
        `;
    }
    
    function hideLoading() {
        // ローディング表示を非表示にする処理は renderProducts() 内で行う
    }
    
    // 初期表示時に年齢が選択されている場合
    if (ageGroupSelect.value) {
        loadProducts(ageGroupSelect.value);
    }
});
</script>
';

$content = ob_get_clean();
include ROOT_PATH . '/views/layout.php';
?>