# Laravel CRUD Generator

A powerful Laravel package that automatically generates complete CRUD (Create, Read, Update, Delete) structure with repository pattern, service classes, professional Bootstrap views, and RESTful APIs. Save hours of development time with a single command!

## 🚀 Features

- **✅ Complete CRUD Structure** - Models, Controllers, Views, Routes, APIs, and more
- **✅ Repository & Service Pattern** - Clean architecture with automatic interface binding
- **✅ Relationship Support** - All Eloquent relationship types (hasMany, belongsTo, belongsToMany, etc.)
- **✅ Professional AdminLTE UI** - Beautiful, responsive admin interface
- **✅ RESTful API Ready** - Complete API endpoints with relationship support
- **✅ Audit Trail** - Automatic `created_by`, `updated_by`, `deleted_by` tracking
- **✅ Form Validation** - Automatic request validation with relationship rules
- **✅ Layout System** - Professional admin layout automatically created
- **✅ Customizable Fields** - Support for various field types and validation
- **✅ Sample Data** - Optional sample data generation

## 📦 Installation

Install the package via Composer:

```bash
composer require aminul/crud-generate
```

That's it! No configuration needed.

## ⚡ Quick Start

Generate a complete CRUD for any model with a single command:

```bash
php artisan make:crud Product --fields="name:string,description:text,price:decimal,is_active:boolean"
```

## 🎯 Relationship Examples

### 1. One-to-Many Relationship (Blog System)

**Post with Comments and Category:**
```bash
# Create Category first
php artisan make:crud Category --fields="name:string,description:text,is_active:boolean"

# Create Post with Category relationship
php artisan make:crud Post --fields="title:string,content:text,is_published:boolean" --relations="belongsTo:Category,hasMany:Comment"

# Create Comment with Post and User relationships
php artisan make:crud Comment --fields="content:text,is_approved:boolean" --relations="belongsTo:Post,belongsTo:User"
```

### 2. Many-to-Many Relationship (E-commerce)

**Product with Categories and Tags:**
```bash
# Create Product with multiple categories and tags
php artisan make:crud Product --fields="name:string,sku:string:unique,description:text,price:decimal,quantity:integer" --relations="belongsToMany:Category,belongsToMany:Tag"

# Create Category
php artisan make:crud Category --fields="name:string,description:text"

# Create Tag
php artisan make:crud Tag --fields="name:string,color:string"
```

### 3. Polymorphic Relationships (Comment System)

**Comments that can belong to Posts or Videos:**
```bash
# Create polymorphic Comment
php artisan make:crud Comment --fields="content:text,is_approved:boolean" --relations="morphTo:commentable,belongsTo:User"

# Create Post with comments
php artisan make:crud Post --fields="title:string,content:text" --relations="morphMany:Comment"

# Create Video with comments
php artisan make:crud Video --fields="title:string,url:string,duration:integer" --relations="morphMany:Comment"
```

## 🛠 Supported Relationship Types

| Relationship | Command Syntax | Description |
|-------------|----------------|-------------|
| **One-to-One** | `hasOne:Profile` | One model has one related model |
| **One-to-Many** | `hasMany:Comment` | One model has many related models |
| **Many-to-One** | `belongsTo:User` | Many models belong to one parent |
| **Many-to-Many** | `belongsToMany:Tag` | Models have many-to-many relationship |
| **Polymorphic One** | `morphOne:Image` | One polymorphic relationship |
| **Polymorphic Many** | `morphMany:Comment` | Many polymorphic relationships |
| **Polymorphic To** | `morphTo:commentable` | Inverse polymorphic relationship |

## 📁 Generated Structure

When you run the command, it creates:

```
app/
├── Models/
│   ├── BaseModel.php          # Base model with audit trail
│   ├── Post.php               # Your model with relationships
│   └── Comment.php
├── Repositories/
│   ├── Contracts/
│   │   ├── PostRepositoryInterface.php
│   │   └── CommentRepositoryInterface.php
│   ├── PostRepository.php     # With relationship methods
│   └── CommentRepository.php
├── Services/
│   ├── PostService.php        # With relationship business logic
│   └── CommentService.php
├── Http/
│   ├── Controllers/
│   │   ├── PostController.php # With relation data loading
│   │   ├── CommentController.php
│   │   └── API/
│   │       ├── PostController.php     # API with relations
│   │       └── CommentController.php
│   └── Requests/
│       ├── StorePostRequest.php       # With relation validation
│       ├── UpdatePostRequest.php
│       ├── StoreCommentRequest.php
│       └── UpdateCommentRequest.php
resources/
└── views/
    ├── layouts/
    │   └── app.blade.php      # Professional AdminLTE layout
    ├── post/
    │   ├── index.blade.php    # With relation data display
    │   ├── create.blade.php   # With relation dropdowns
    │   ├── edit.blade.php
    │   └── show.blade.php     # With relation details
    └── comment/
        ├── index.blade.php
        ├── create.blade.php
        ├── edit.blade.php
        └── show.blade.php
database/
└── migrations/
    ├── create_posts_table.php         # With foreign keys
    └── create_comments_table.php      # With relation columns
routes/
├── web.php                    # Web routes with relations
└── api.php                    # API routes with relation endpoints
```

## 🌐 Automatic Web Features

### Form Dropdowns
Relationships automatically generate dropdown selects in forms:

```html
<!-- Generated in create/edit forms -->
<div class="form-group">
    <label for="category_id">Category <span class="text-danger">*</span></label>
    <select name="category_id" id="category_id" class="form-control select2" required>
        <option value="">Select Category</option>
        @foreach($categories as $category)
            <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                {{ $category->name }}
            </option>
        @endforeach
    </select>
</div>
```

### Data Display
Relationships are displayed in lists and show pages:

```php
// In index view - shows related data
<td>{{ $post->category->name }}</td>
<td>{{ $post->user->name }}</td>

// In show view - shows full relationship details
<p><strong>Category:</strong> {{ $post->category->name }}</p>
<p><strong>Author:</strong> {{ $post->user->name }}</p>
<p><strong>Comments:</strong> {{ $post->comments_count }}</p>
```

## 🔌 API Endpoints with Relations

### Eager Loading
Load relationships via query parameters:
```http
GET /api/posts?with=category,user,comments
GET /api/posts/1?with=category,comments.user
```

### Related Data Endpoints
Get data for form dropdowns:
```http
GET /api/posts/related-data
```

### Nested Resources
Access related models directly:
```http
GET /api/posts/1/comments
GET /api/categories/1/posts
```

## 🎨 Professional UI Features

- **AdminLTE 3** - Professional admin interface
- **DataTables** - Advanced tables with search and sort
- **Select2** - Enhanced dropdowns for relationships
- **Font Awesome** - Beautiful icons throughout
- **SweetAlert2** - Elegant confirmation dialogs
- **Responsive Design** - Works on all devices
- **Relationship Display** - Shows related data beautifully
- **Audit Trail** - Track creation and updates

## 🚨 After Generation

1. **Run migrations:**
   ```bash
   php artisan migrate
   ```

2. **Access your CRUD:**
   - Web: Visit `/posts` and `/comments`
   - API: Use `/api/posts` and `/api/comments` endpoints

3. **Test relationships:**
   - Create posts with categories
   - Add comments to posts
   - View related data in lists and details


## 📝 Field Types Supported

| Type | Example | Description |
|------|---------|-------------|
| `string` | `name:string` | Short text (VARCHAR) |
| `text` | `description:text` | Long text (TEXT) |
| `integer` | `quantity:integer` | Whole numbers |
| `decimal` | `price:decimal` | Decimal numbers |
| `boolean` | `is_active:boolean` | True/False |
| `date` | `birth_date:date` | Date only |
| `datetime` | `published_at:datetime` | Date and time |
| `email` | `email:string:unique` | Email with validation |
| `:unique` | `sku:string:unique` | Unique constraint |

## 🎯 Perfect For

- **Admin Panels** - Quick backend with relationships
- **Prototyping** - Rapid application development
- **MVP Development** - Get to market faster
- **Learning** - Study Laravel best practices with relationships
- **Team Projects** - Consistent code structure
- **Complex Systems** - Multi-model applications with relations

## ⚡ Pro Tips

1. **Generate related models first** - Create parent models before children
2. **Use `--sample` flag** - For testing with sample data
3. **Chain relationships** - Build complex systems step by step
4. **API first** - Use `?with=relation` parameter for eager loading
5. **Customize generated code** - All code follows Laravel best practices

---

**⭐ Star the repository if you find this package helpful!**

**💡 Pro Tip:** Start with simple relationships and gradually build complex systems. The package handles all the boilerplate code for you!

---

*Built with ❤️ for the Laravel community*