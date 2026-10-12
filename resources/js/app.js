import './bootstrap';
import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';
import {
    createIcons, Bike, Map, Footprints, Tag, Image, MapPin, Search, Bell, Menu, X, ChevronDown, ChevronRight,
    ChevronLeft, ArrowRight, ArrowUp, Phone, Instagram, Star, ShieldCheck, User, Users, Clock, CalendarDays,
    Wallet, LayoutDashboard, Settings, FileText, CreditCard, ChartColumn, LogOut, Plus, Pencil, Trash2, Check,
    Eye, Wrench, MessageCircle, Gauge, Cog, Fuel, Receipt, Banknote, Upload, Download, Printer, Package,
    Percent, Ban, Undo2, Truck, ClipboardCheck, ExternalLink, Facebook, Mail, Home, Lock, Info, TriangleAlert,
    CircleCheck, Layers, Flag, Mountain, Route, ArrowLeft, Save, Filter, Store, UserCog, Calendar,
} from 'lucide';

window.Alpine = Alpine;
window.Chart = Chart;

const icons = {
    Bike, Map, Footprints, Tag, Image, MapPin, Search, Bell, Menu, X, ChevronDown, ChevronRight, ChevronLeft,
    ArrowRight, ArrowUp, Phone, Instagram, Star, ShieldCheck, User, Users, Clock, CalendarDays, Wallet,
    LayoutDashboard, Settings, FileText, CreditCard, ChartColumn, LogOut, Plus, Pencil, Trash2, Check, Eye,
    Wrench, MessageCircle, Gauge, Cog, Fuel, Receipt, Banknote, Upload, Download, Printer, Package, Percent,
    Ban, Undo2, Truck, ClipboardCheck, ExternalLink, Facebook, Mail, Home, Lock, Info, TriangleAlert,
    CircleCheck, Layers, Flag, Mountain, Route, ArrowLeft, Save, Filter, Store, UserCog, Calendar,
};
window.refreshIcons = () => createIcons({ icons });

const rupiah = (n) => 'Rp' + Math.round(Number(n) || 0).toLocaleString('id-ID');

/* ---------- dialog konfirmasi global ---------- */
Alpine.store('confirm', {
    open: false,
    message: '',
    form: null,
    ask(message, form) {
        this.message = message;
        this.form = form;
        this.open = true;
    },
    accept() {
        this.open = false;
        if (this.form) this.form.submit();
    },
});

/* ---------- toast ---------- */
Alpine.data('toast', (message, type) => ({
    show: !!message,
    message,
    type,
    init() {
        if (this.show) setTimeout(() => (this.show = false), 5000);
    },
}));

/* ---------- kalender ketersediaan motor ---------- */
Alpine.data('availabilityCalendar', (booked) => ({
    booked,
    cursor: new Date(new Date().getFullYear(), new Date().getMonth(), 1),
    get title() {
        return this.cursor.toLocaleDateString('id-ID', { month: 'long', year: 'numeric' });
    },
    get cells() {
        const y = this.cursor.getFullYear();
        const m = this.cursor.getMonth();
        const first = (new Date(y, m, 1).getDay() + 6) % 7; // Senin = 0
        const days = new Date(y, m + 1, 0).getDate();
        const out = [];
        for (let i = 0; i < first; i++) out.push(null);
        for (let d = 1; d <= days; d++) {
            const iso = `${y}-${String(m + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
            const today = new Date().toLocaleDateString('en-CA');
            out.push({ d, iso, booked: this.isBooked(iso), past: iso < today });
        }
        return out;
    },
    isBooked(iso) {
        return this.booked.some((r) => iso >= r.start && iso < r.end);
    },
    move(n) {
        this.cursor = new Date(this.cursor.getFullYear(), this.cursor.getMonth() + n, 1);
    },
}));

/* ---------- tanggal lokal (YYYY-MM-DD), bebas pergeseran zona waktu ---------- */
const localToday = () => new Date().toLocaleDateString('en-CA');
const addDay = (iso, n = 1) => {
    const d = new Date(iso + 'T00:00:00Z');
    d.setUTCDate(d.getUTCDate() + n);
    return d.toISOString().slice(0, 10);
};

/* ---------- form booking: hitung harga langsung dari server ---------- */
Alpine.data('bookingForm', (cfg) => ({
    isJeep: !!cfg.isJeep,
    start: cfg.start,
    end: cfg.end,
    location: cfg.location || '',
    promo: cfg.promo || '',
    quantity: cfg.quantity || 1,
    people: cfg.people || '',
    packageId: cfg.packageId || '',
    addons: (cfg.addons || []).map(String),
    quote: null,
    loading: false,
    method: cfg.method || '',
    methodType: '',
    methods: cfg.methods,
    today: localToday(),
    rupiah,
    init() {
        if (this.isJeep) this.end = addDay(this.start);
        this.$watch('start', () => {
            if (this.isJeep || this.end <= this.start) this.end = addDay(this.start);
            this.fetchQuote();
        });
        ['end', 'location', 'quantity', 'people', 'packageId'].forEach((k) => this.$watch(k, () => this.fetchQuote()));
        this.$watch('addons', () => this.fetchQuote());
        this.$watch('method', (v) => (this.methodType = (this.methods.find((m) => String(m.id) === String(v)) || {}).type || ''));
        this.methodType = (this.methods.find((m) => String(m.id) === String(this.method)) || {}).type || '';
        this.fetchQuote();
    },
    async fetchQuote() {
        if (!this.start || !this.end) return;
        this.loading = true;
        try {
            const { data } = await window.axios.post(cfg.quoteUrl, {
                motor_id: cfg.motorId,
                start_date: this.start,
                end_date: this.end,
                promo_code: this.promo || null,
                location_id: this.location || null,
                quantity: Number(this.quantity) || 1,
                package_id: this.packageId || null,
                people: this.isJeep && this.people ? Number(this.people) : null,
                addons: this.addons.map(Number),
            });
            this.quote = data;
        } catch (e) {
            this.quote = { available: false, errors: ['Gagal menghitung harga. Coba lagi.'] };
        } finally {
            this.loading = false;
        }
    },
}));

/* ---------- filter galeri ---------- */
Alpine.data('galleryFilter', () => ({
    cat: 'semua',
    lightbox: null,
}));

/* ---------- grafik dashboard admin ---------- */
window.renderAdminCharts = (cfg) => {
    const grid = 'rgba(15,23,42,.08)';
    const tick = '#64748b';
    const base = { responsive: true, maintainAspectRatio: false, plugins: { legend: { labels: { color: tick, boxWidth: 10 } } } };
    const axes = { x: { grid: { color: grid }, ticks: { color: tick } }, y: { grid: { color: grid }, ticks: { color: tick }, beginAtZero: true } };

    const week = document.getElementById('chartWeek');
    if (week) {
        new Chart(week, {
            type: 'line',
            data: {
                labels: cfg.week.labels,
                datasets: [
                    { label: 'Jumlah rental', data: cfg.week.rentals, borderColor: '#3b82f6', backgroundColor: 'rgba(59,130,246,.15)', fill: true, tension: 0.35, yAxisID: 'y' },
                    { label: 'Pendapatan (Rp)', data: cfg.week.revenue, borderColor: '#ffc72c', backgroundColor: 'rgba(255,199,44,.12)', fill: true, tension: 0.35, yAxisID: 'y1' },
                ],
            },
            options: { ...base, scales: { x: axes.x, y: { ...axes.y, ticks: { color: tick, precision: 0 } }, y1: { position: 'right', grid: { drawOnChartArea: false }, ticks: { color: tick, callback: (v) => rupiah(v) }, beginAtZero: true } } },
        });
    }
    const month = document.getElementById('chartMonth');
    if (month) {
        new Chart(month, {
            type: 'bar',
            data: { labels: cfg.monthly.labels, datasets: [{ label: 'Pendapatan bulanan', data: cfg.monthly.revenue, backgroundColor: '#ffc72c', borderRadius: 8 }] },
            options: { ...base, scales: { x: axes.x, y: { ...axes.y, ticks: { color: tick, callback: (v) => rupiah(v) } } } },
        });
    }
    const top = document.getElementById('chartTop');
    if (top && cfg.top.labels.length) {
        new Chart(top, {
            type: 'doughnut',
            data: { labels: cfg.top.labels, datasets: [{ data: cfg.top.values, backgroundColor: ['#3b82f6', '#22c55e', '#ffc72c', '#ef4444', '#a855f7'], borderWidth: 0 }] },
            options: { ...base, cutout: '62%', plugins: { legend: { position: 'right', labels: { color: tick, boxWidth: 10 } } } },
        });
    }
};

Alpine.start();
window.refreshIcons();
