// نظام لغة خفيف AR/EN للموقع العام — بلا حزم خارجية
const DICT: Record<string, string> = {
  // header
  'الرئيسية': 'Home',
  'سياراتنا': 'Our cars',
  'تواصل': 'Contact',
  'دخول المعرض': 'Showroom login',
  // hero
  'تصفّح السيارات': 'Browse cars',
  'تواصل معنا': 'Contact us',
  // search card
  'ابحث عن سيارتك': 'Find your car',
  'الماركة': 'Make',
  'الموديل': 'Model',
  'الوقود': 'Fuel type',
  'السنة من': 'Year from',
  'ناقل الحركة': 'Transmission',
  'الممشى حتى': 'Max mileage',
  'كل الماركات': 'All makes',
  'كل الموديلات': 'All models',
  'اختر الماركة أولاً': 'Select make first',
  'الكل': 'All',
  'أي سنة': 'Any year',
  'أي ممشى': 'Any mileage',
  'بحث متقدّم': 'Advanced search',
  'بنزين': 'Petrol',
  'ديزل': 'Diesel',
  'هايبرد': 'Hybrid',
  'كهرباء': 'Electric',
  'أوتوماتيك': 'Automatic',
  'عادي': 'Manual',
  'كم': 'km',
  // sections
  'عرض الكل ‹': 'View all ›',
  'أحدث ما وصل إلى معرضنا': 'The latest arrivals at our showroom',
  'سيارات مميّزة': 'Featured cars',
  'جارِ التحميل…': 'Loading…',
  'لا توجد سيارات معروضة حالياً': 'No cars available right now',
  // cards
  'مميّزة': 'Featured',
  'محجوزة': 'Reserved',
  'التفاصيل ‹': 'Details ›',
  // cars page
  'كل السيارات المتوفّرة في المعرض': 'All cars available at the showroom',
  'تصفية النتائج': 'Filter results',
  'بحث': 'Search',
  'ماركة أو موديل': 'Make or model',
  'أقصى سعر ($)': 'Max price ($)',
  'تطبيق': 'Apply',
  'مسح': 'Clear',
  'الأحدث': 'Latest',
  'السعر: الأقل': 'Price: lowest',
  'السعر: الأعلى': 'Price: highest',
  'لا توجد نتائج مطابقة': 'No matching results',
  'سيارة': 'vehicles',
  // car detail
  'رجوع للسيارات': 'Back to cars',
  'المواصفات': 'Specifications',
  'السنة': 'Year',
  'الممشى': 'Mileage',
  'اللون': 'Color',
  'الدفع': 'Drivetrain',
  'المميزات': 'Features',
  'تواصل عبر واتساب': 'Chat on WhatsApp',
  'اتصل بالمعرض': 'Call the showroom',
  'أرسل استفسار': 'Send an inquiry',
  'شامل ضمان المعرض': 'Includes showroom warranty',
  'سيارات مفحوصة ومضمونة': 'Inspected & warranted cars',
  'المعرض': 'Showroom',
  'لم يتم العثور على السيارة': 'Car not found',
  // contact
  'أرسل رسالة': 'Send a message',
  'الاسم': 'Name',
  'رقم الهاتف': 'Phone number',
  'رسالتك': 'Your message',
  'إرسال': 'Send',
  'وصلنا استفسارك — سنتواصل معك قريباً': 'We received your inquiry — we will contact you soon',
  // footer
  'روابط سريعة': 'Quick links',
  'معلومات التواصل': 'Contact info',
  'جميع الحقوق محفوظة': 'All rights reserved',
}

// النصوص الافتراضية الإنجليزية لنصوص الموقع القابلة للتعديل (CMS)
const CMS_EN_DEFAULTS: Record<string, string> = {
  hero_badge: 'Certified car showroom',
  hero_title: 'Your next car is waiting at Auto Gallery',
  hero_subtitle: 'A curated selection of inspected cars at competitive prices, backed by our showroom warranty.',
  search_title: 'Find your car',
  featured_title: 'Featured cars',
  featured_subtitle: 'The latest arrivals at our showroom',
  why_title: 'Why choose us?',
  why1_title: 'Inspected cars', why1_text: 'Every car passes a full inspection before listing.',
  why2_title: 'Transparent pricing', why2_text: 'No surprises — the listed price is the price.',
  why3_title: 'Showroom warranty', why3_text: 'Engine & gearbox warranty depending on the car.',
  why4_title: 'After-sales service', why4_text: 'Our team stays with you after delivery.',
  cta_title: 'Have a car to sell?',
  cta_text: 'We buy your car or list it in our showroom — contact us today.',
  cta_button: 'Contact us now',
  footer_text: 'A car showroom offering a curated selection of inspected cars at competitive prices.',
}

export function useLang() {
  const lang = useCookie<'ar' | 'en'>('sr_lang', { default: () => 'ar', maxAge: 60 * 60 * 24 * 365 })
  const { settings } = useSettings()

  const isEn = computed(() => lang.value === 'en')

  // ترجمة نصوص الواجهة الثابتة
  const t = (s: string) => (isEn.value ? (DICT[s] ?? s) : s)

  // نصوص الموقع القابلة للتعديل: بالعربي من settings، بالإنجليزي من settings[key_en] أو الافتراضي
  const cms = (key: string, arFallback = '') => {
    if (isEn.value) return settings.value[key + '_en'] || CMS_EN_DEFAULTS[key] || settings.value[key] || arFallback
    return settings.value[key] || arFallback
  }

  // قيمة إعداد حسب اللغة: بالإنجليزي يجرب key_en أولاً
  const loc = (key: string) => (isEn.value ? (settings.value[key + '_en'] || settings.value[key] || '') : (settings.value[key] || ''))

  function toggleLang() {
    lang.value = isEn.value ? 'ar' : 'en'
  }

  // تطبيق الاتجاه واللغة على الصفحة
  useHead({
    htmlAttrs: {
      lang: computed(() => (isEn.value ? 'en' : 'ar')),
      dir: computed(() => (isEn.value ? 'ltr' : 'rtl')),
    },
  })

  return { lang, isEn, t, cms, loc, toggleLang }
}
