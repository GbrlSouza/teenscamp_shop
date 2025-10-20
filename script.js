// script.js
let products = [];
let cart = JSON.parse(localStorage.getItem('cart')) || [];
let adminToken = localStorage.getItem('adminToken') || null;

const API_URL = './api.php'; 
const productsContainer = document.getElementById('products-container');
const cartCount = document.getElementById('cart-count');
const cartSubtotal = document.getElementById('cart-subtotal');
const cartItems = document.getElementById('cart-items');
const adminProductsList = document.getElementById('admin-products-list');
const addProductForm = document.getElementById('add-product-form');
const adminLoginForm = document.getElementById('admin-login-form');

// Modals Bootstrap
const cartModal = new bootstrap.Modal(document.getElementById('cartModal'));
const adminLoginModal = new bootstrap.Modal(document.getElementById('adminLoginModal'));
const adminPanelModal = new bootstrap.Modal(document.getElementById('adminPanelModal'));

// Botões/Eventos
document.getElementById('cart-btn').addEventListener('click', () => { renderCart(); cartModal.show(); });
document.getElementById('checkout-btn').addEventListener('click', checkout);
document.getElementById('admin-btn').addEventListener('click', (e) => {
    e.preventDefault();
    if (adminToken) {
        adminPanelModal.show();
        renderAdminProducts();
    } else {
        adminLoginModal.show();
    }
});

// Manipuladores de Filtro
document.querySelectorAll('.btn-group button').forEach(button => {
    button.addEventListener('click', (e) => {
        const category = e.target.getAttribute('data-category');
        document.querySelectorAll('.btn-group button').forEach(b => b.classList.remove('active'));
        e.target.classList.add('active');
        filterProducts(category);
    });
});


function buildProductUrl(productId = null) {
    return productId ? `${API_URL}?id=${productId}` : API_URL;
}

// === API / Lógica de Dados ===

async function fetchProducts() {
    try {
        const response = await fetch(API_URL);
        products = await response.json();
        renderProducts(products);
    } catch (error) {
        productsContainer.innerHTML = `<div class="col-12"><div class="alert alert-danger text-center" role="alert">Erro ao carregar produtos. Tente novamente mais tarde.</div></div>`;
        console.error('Erro ao buscar produtos:', error);
    }
}

async function deleteProduct(productId) {
    if (!confirm('Tem certeza de que deseja EXCLUIR este produto?')) return;
    
    try {
        const response = await fetch(buildProductUrl(productId), {
            method: 'DELETE',
            headers: { 'X-Admin-Token': adminToken }
        });

        const result = await response.json();

        if (response.ok) {
            alert(result.message);
            renderAdminProducts(); // Recarrega a lista
            fetchProducts(); // Recarrega a loja
        } else {
            alert(`Erro ao excluir: ${result.error || 'Falha na comunicação.'}`);
        }
    } catch (error) {
        console.error('Erro de exclusão:', error);
        alert('Erro de conexão ao tentar excluir o produto.');
    }
}


// === Lógica de Renderização / UI ===

function filterProducts(category) {
    const filtered = (category === 'all')
        ? products
        : products.filter(p => p.category === category);
    renderProducts(filtered);
}

function renderProducts(productList) {
    productsContainer.innerHTML = ''; // Limpa o container
    
    if (productList.length === 0) {
        productsContainer.innerHTML = `<div class="col-12"><p class="text-center text-muted">Nenhum produto encontrado nesta categoria.</p></div>`;
        return;
    }

    productList.forEach(product => {
        const card = document.createElement('div');
        card.className = 'col';
        card.innerHTML = `
            <div class="card h-100 shadow-sm border-0 product-card" style="transition: transform 0.3s, box-shadow 0.3s; cursor: pointer;">
                <img src="${product.image_url}" class="card-img-top" alt="${product.name}" style="height: 200px; object-fit: cover;">
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title text-danger fw-bold">${product.name}</h5>
                    <p class="card-text text-muted flex-grow-1">${product.description.substring(0, 70)}...</p>
                    <div class="d-flex justify-content-between align-items-center mt-auto">
                        <span class="fs-4 fw-bolder text-dark">R$ ${parseFloat(product.price).toFixed(2).replace('.', ',')}</span>
                        <button class="btn btn-danger btn-sm" onclick="addToCart(${product.id})">
                            <i class="fas fa-plus me-1"></i> Adicionar
                        </button>
                    </div>
                </div>
            </div>
        `;
        productsContainer.appendChild(card);
    });
}

function renderCart() {
    cartItems.innerHTML = '';
    let subtotal = 0;

    if (cart.length === 0) {
        cartItems.innerHTML = '<li class="list-group-item text-muted text-center">O carrinho está vazio.</li>';
    } else {
        cart.forEach((item, index) => {
            const product = products.find(p => p.id === item.id);
            if (product) {
                const total = product.price * item.quantity;
                subtotal += total;

                const li = document.createElement('li');
                li.className = 'list-group-item d-flex justify-content-between align-items-center';
                li.innerHTML = `
                    <div class="d-flex align-items-center">
                        <img src="${product.image_url}" alt="${product.name}" style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;" class="me-3">
                        <div>
                            <h6 class="mb-0 fw-bold">${product.name}</h6>
                            <small class="text-muted">R$ ${parseFloat(product.price).toFixed(2).replace('.', ',')} x ${item.quantity}</small>
                        </div>
                    </div>
                    <div>
                        <span class="fw-bold text-danger">R$ ${total.toFixed(2).replace('.', ',')}</span>
                        <button class="btn btn-sm btn-outline-danger ms-2" onclick="removeFromCart(${index})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                `;
                cartItems.appendChild(li);
            }
        });
    }

    cartSubtotal.textContent = `R$ ${subtotal.toFixed(2).replace('.', ',')}`;
    cartCount.textContent = cart.reduce((sum, item) => sum + item.quantity, 0);
    localStorage.setItem('cart', JSON.stringify(cart));
}

function renderAdminProducts() {
    adminProductsList.innerHTML = '';
    if (!products.length) {
        adminProductsList.innerHTML = '<p class="text-center text-muted">A loja não tem produtos cadastrados.</p>';
        return;
    }

    products.forEach(product => {
        const item = document.createElement('div');
        item.className = 'list-group-item d-flex justify-content-between align-items-center';
        item.innerHTML = `
            <div class="d-flex align-items-center">
                <img src="${product.image_url}" alt="${product.name}" style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;" class="me-3">
                <div>
                    <h6 class="mb-0 fw-bold">${product.name}</h6>
                    <small class="text-muted">ID: ${product.id} | Preço: R$ ${parseFloat(product.price).toFixed(2).replace('.', ',')}</small>
                </div>
            </div>
            <button class="btn btn-sm btn-danger" onclick="deleteProduct(${product.id})">
                <i class="fas fa-trash"></i> Excluir
            </button>
        `;
        adminProductsList.appendChild(item);
    });
}

// === Lógica de Carrinho ===

function addToCart(id) {
    const existingItem = cart.find(item => item.id === id);
    if (existingItem) {
        existingItem.quantity++;
    } else {
        cart.push({ id, quantity: 1 });
    }
    renderCart();
    alert('Produto adicionado ao carrinho!');
}

function removeFromCart(index) {
    cart.splice(index, 1);
    renderCart();
}

function checkout() {
    if (cart.length === 0) {
        alert('Seu carrinho está vazio.');
        return;
    }

    const whatsappBase = 'https://api.whatsapp.com/send?phone=55DDONUMERO&text=';
    let message = "Olá! Gostaria de fazer o seguinte pedido da Teens Camp Shop:\n\n";
    let total = 0;

    cart.forEach(item => {
        const product = products.find(p => p.id === item.id);
        if (product) {
            const itemTotal = product.price * item.quantity;
            total += itemTotal;
            message += `- ${item.quantity}x ${product.name} (R$ ${product.price.toFixed(2).replace('.', ',')})\n`;
        }
    });

    message += `\n*TOTAL: R$ ${total.toFixed(2).replace('.', ',')}*`;
    message += "\n\nPor favor, confirme a disponibilidade e o valor do frete.";
    
    // NOTA: Você precisa alterar 'DDONUMERO' para o número de WhatsApp da sua loja.
    const finalUrl = whatsappBase.replace('DDONUMERO', 'SEUNUMERODD').replace(' ', '') + encodeURIComponent(message);

    window.open(finalUrl, '_blank');
    
    // Limpar o carrinho após a finalização (opcional)
    cart = [];
    renderCart();
    cartModal.hide();
}

// === Lógica de Admin ===

adminLoginForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const username = document.getElementById('username').value;
    const password = document.getElementById('password').value;
    
    try {
        const response = await fetch(`${API_URL}?action=login`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ username, password })
        });
        
        const result = await response.json();
        
        if (response.ok && result.success) {
            adminToken = result.token;
            localStorage.setItem('adminToken', adminToken);
            adminLoginModal.hide();
            adminPanelModal.show();
            renderAdminProducts();
        } else { alert(result.message || 'Usuário ou senha incorretos!'); }
        
    } catch(error) {
        console.error('Erro de login:', error);
        alert('Erro de conexão ao tentar fazer login.');
    }
});

addProductForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const formData = new FormData();
    formData.append('name', document.getElementById('product-name').value);
    formData.append('price', document.getElementById('product-price').value);
    formData.append('category', document.getElementById('product-category').value);
    formData.append('description', document.getElementById('product-description').value);
    formData.append('image', document.getElementById('product-image').files[0]);
    
    try {
        const response = await fetch(API_URL, {
            method: 'POST',
            headers: { 'X-Admin-Token': adminToken },
            body: formData // Não defina 'Content-Type' para FormData, o browser faz isso automaticamente
        });
        
        const result = await response.json();
        
        if (response.ok) {
            alert(result.message);
            addProductForm.reset();
            fetchProducts(); // Recarrega a loja e o painel admin
        } else {
            alert(`Erro ao adicionar: ${result.error || 'Falha na comunicação.'}`);
        }
        
    } catch(error) {
        console.error('Erro ao adicionar produto:', error);
        alert('Erro de conexão ao tentar adicionar o produto.');
    }
});
