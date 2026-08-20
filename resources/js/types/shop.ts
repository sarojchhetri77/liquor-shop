export type ProductImage = {
    id: number;
    url: string;
    is_primary: boolean;
    sort_order: number;
};

export type Category = {
    id: number;
    name: string;
    slug: string;
    description?: string | null;
    image?: string | null;
    image_url?: string | null;
    products_count?: number;
};

export type Brand = {
    id: number;
    name: string;
    slug?: string;
    products_count?: number;
};

export type Review = {
    id: number;
    rating: number;
    comment: string | null;
    created_at: string;
    user?: { id: number; name: string };
};

export type Product = {
    id: number;
    category_id: number;
    brand_id: number | null;
    name: string;
    slug: string;
    description: string | null;
    brand?: Brand | null;
    price: string;
    final_price: number;
    discount_percent: number;
    effective_discount_percent: number;
    is_discount_active: boolean;
    discount_starts_at: string | null;
    discount_ends_at: string | null;
    rating: string;
    reviews_count: number;
    stock: number;
    is_active: boolean;
    images?: ProductImage[];
    category?: Category;
    reviews?: Review[];
    created_at?: string;
};

export type Promotion = {
    id: number;
    title: string;
    image_url: string;
    link: string | null;
    is_active: boolean;
    sort_order: number;
};

export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export type Paginated<T> = {
    data: T[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
};

export type OrderItem = {
    id: number;
    product_id: number | null;
    product_name: string;
    unit_price: string;
    quantity: number;
    line_total: string;
    product?: Product | null;
};

export type Order = {
    id: number;
    order_number: string;
    status: string;
    payment_method: string;
    subtotal: string;
    total: string;
    customer_name: string;
    contact: string;
    shipping_address: string;
    note: string | null;
    items?: OrderItem[];
    items_count?: number;
    user?: { id: number; name: string; email: string };
    can_cancel: boolean;
    cancellable_until: string | null;
    created_at: string;
};
