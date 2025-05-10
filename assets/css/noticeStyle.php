<style>
    :root {
        --primary-color: #4361ee;
        --secondary-color: #3f37c9;
        --success-color: #4cc9f0;
        --danger-color: #f72585;
        --warning-color: #f8961e;
        --info-color: #4895ef;
        --light-color: #f8f9fa;
        --dark-color: #212529;
        --border-radius: 0.5rem;
        --transition: all 0.3s ease;
    }

    .notice-card {
        border: 1px solid #e9ecef;
        border-left: 4px solid;
        transition: var(--transition);
        border-radius: var(--border-radius);
        height: 100%;
        display: flex;
        flex-direction: column;
        background: #fff;
        position: relative;
        overflow: hidden;
    }

    .notice-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    }

    .notice-urgent {
        border-left-color: var(--danger-color);
        background: linear-gradient(to right, rgba(247, 37, 133, 0.05), transparent);
    }

    .notice-important {
        border-left-color: var(--warning-color);
        background: linear-gradient(to right, rgba(248, 150, 30, 0.05), transparent);
    }

    .notice-normal {
        border-left-color: var(--info-color);
        background: linear-gradient(to right, rgba(72, 149, 239, 0.05), transparent);
    }

    .badge-urgent {
        background-color: var(--danger-color);
        color: white;
        font-weight: 500;
    }

    .badge-important {
        background-color: var(--warning-color);
        color: white;
        font-weight: 500;
    }

    .badge-normal {
        background-color: var(--info-color);
        color: white;
        font-weight: 500;
    }

    .attachment-badge {
        background-color: rgba(233, 236, 239, 0.5);
        color: #495057;
        border-radius: 20px;
        padding: 5px 12px;
        font-size: 0.8rem;
        margin-right: 5px;
        margin-bottom: 5px;
        display: inline-flex;
        align-items: center;
        transition: var(--transition);
        border: 1px solid rgba(0, 0, 0, 0.05);
    }

    .attachment-badge i {
        margin-right: 5px;
    }

    .empty-state {
        background-color: #f8f9fa;
        border-radius: var(--border-radius);
        padding: 60px 30px;
        text-align: center;
        border: 2px dashed #dee2e6;
    }

    .empty-state-icon {
        font-size: 4rem;
        color: #adb5bd;
        margin-bottom: 20px;
        animation: bounce 2s infinite;
    }

    @keyframes bounce {

        0%,
        20%,
        50%,
        80%,
        100% {
            transform: translateY(0);
        }

        40% {
            transform: translateY(-20px);
        }

        60% {
            transform: translateY(-10px);
        }
    }

    .floating-btn {
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 20px rgba(67, 97, 238, 0.3);
        z-index: 1000;
        transition: var(--transition);
        background: var(--primary-color);
        color: white;
        border: none;
    }

    .floating-btn:hover {
        transform: scale(1.1);
        box-shadow: 0 6px 25px rgba(67, 97, 238, 0.4);
    }

    .date-badge {
        background-color: rgba(233, 236, 239, 0.5);
        color: #495057;
        border-radius: 20px;
        padding: 4px 12px;
        font-size: 0.75rem;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        border: 1px solid rgba(0, 0, 0, 0.05);
    }

    .file-preview {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        margin-top: 15px;
        padding: 15px;
        background: rgba(248, 249, 250, 0.5);
        border-radius: var(--border-radius);
        border: 1px dashed #dee2e6;
    }

    .file-preview-item {
        background: white;
        border: 1px solid rgba(0, 0, 0, 0.05);
        border-radius: var(--border-radius);
        padding: 10px 15px;
        display: flex;
        align-items: center;
        font-size: 0.8rem;
        transition: var(--transition);
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
    }

    .file-preview-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 10px rgba(0, 0, 0, 0.1);
    }

    .file-preview-item i {
        margin-right: 5px;
        color: #6c757d;
    }

    .card-body {
        flex: 1;
        padding: 1.5rem;
    }

    .card-footer {
        background-color: rgba(0, 0, 0, 0.02);
        border-top: 1px solid rgba(0, 0, 0, 0.05);
        padding: 1rem 1.5rem;
    }

    #editor-container {
        height: 250px;
        margin-bottom: 15px;
        border-radius: var(--border-radius);
        overflow: hidden;
    }

    .ql-toolbar {
        border-radius: var(--border-radius) var(--border-radius) 0 0;
        border-color: #dee2e6 !important;
        background: #f8f9fa;
    }

    .ql-container {
        border-radius: 0 0 var(--border-radius) var(--border-radius);
        font-family: inherit;
        border-color: #dee2e6 !important;
    }

    .notice-content img {
        max-width: 100%;
        height: auto;
        border-radius: var(--border-radius);
        margin: 10px 0;
    }

    .attachment-thumbnail {
        width: 120px;
        height: 120px;
        object-fit: cover;
        border-radius: var(--border-radius);
        margin-right: 10px;
        margin-bottom: 10px;
        cursor: pointer;
        transition: var(--transition);
        border: 2px solid white;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }

    .attachment-thumbnail:hover {
        transform: scale(1.05);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    }

    .attachment-preview {
        max-width: 100%;
        max-height: 400px;
        margin-bottom: 15px;
        border-radius: var(--border-radius);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }

    .tab-content {
        padding: 25px 0;
    }

    .notice-filters {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }

    .filter-btn {
        padding: 8px 16px;
        border-radius: 20px;
        border: 1px solid #dee2e6;
        background: white;
        color: #495057;
        font-size: 0.9rem;
        transition: var(--transition);
        cursor: pointer;
    }

    .filter-btn:hover,
    .filter-btn.active {
        background: var(--primary-color);
        color: white;
        border-color: var(--primary-color);
    }

    .notice-search {
        position: relative;
        margin-bottom: 20px;
    }

    .notice-search input {
        width: 100%;
        padding: 12px 20px;
        padding-left: 40px;
        border-radius: var(--border-radius);
        border: 1px solid #dee2e6;
        font-size: 0.9rem;
        transition: var(--transition);
    }

    .notice-search input:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
    }

    .notice-search i {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #adb5bd;
    }

    .dropdown-menu {
        border-radius: var(--border-radius);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        border: 1px solid rgba(0, 0, 0, 0.05);
        padding: 0.5rem;
    }

    .dropdown-item {
        border-radius: var(--border-radius);
        padding: 0.5rem 1rem;
        transition: var(--transition);
    }

    .dropdown-item:hover {
        background-color: rgba(67, 97, 238, 0.1);
        color: var(--primary-color);
    }

    .dropdown-item i {
        width: 20px;
    }

    .modal-content {
        border-radius: var(--border-radius);
        border: none;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }

    .modal-header {
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        padding: 1.5rem;
    }

    .modal-body {
        padding: 1.5rem;
    }

    .modal-footer {
        border-top: 1px solid rgba(0, 0, 0, 0.05);
        padding: 1.5rem;
    }

    .form-control,
    .form-select {
        border-radius: var(--border-radius);
        padding: 0.75rem 1rem;
        border-color: #dee2e6;
        transition: var(--transition);
    }

    .form-control:focus,
    .form-select:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
    }

    .btn {
        border-radius: var(--border-radius);
        padding: 0.75rem 1.5rem;
        font-weight: 500;
        transition: var(--transition);
    }

    .btn-primary {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
    }

    .btn-primary:hover {
        background-color: var(--secondary-color);
        border-color: var(--secondary-color);
        transform: translateY(-1px);
    }

    .pagination {
        gap: 5px;
    }

    .page-link {
        border-radius: var(--border-radius);
        padding: 0.5rem 1rem;
        transition: var(--transition);
    }

    .page-item.active .page-link {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
    }

    .alert {
        border-radius: var(--border-radius);
        border: 1px solid rgba(0, 0, 0, 0.05);
        padding: 1rem 1.5rem;
    }

    .breadcrumb {
        margin-bottom: 0;
    }

    .breadcrumb-item a {
        color: var(--primary-color);
        text-decoration: none;
        transition: var(--transition);
    }

    .breadcrumb-item a:hover {
        color: var(--secondary-color);
    }
</style>




<style>
    /* Inherit Bootstrap theme colors from header */
    :root {
        --primary: var(--bs-primary, #4361ee);
        --primary-light: var(--bs-primary-rgb, #e6e9ff);
        --secondary: var(--bs-secondary, #3f37c9);
        --danger: var(--bs-danger, #f72585);
        --warning: var(--bs-warning, #f8961e);
        --info: var(--bs-info, #4895ef);
        --success: var(--bs-success, #4cc9f0);
        --light: var(--bs-light, #f8f9fa);
        --dark: var(--bs-dark, #212529);
        --gray: var(--bs-gray, #6c757d);
        --white: var(--bs-white, #ffffff);
        --border-radius: var(--bs-border-radius, 8px);
        --box-shadow: var(--bs-box-shadow, 0 4px 12px rgba(0, 0, 0, 0.08));
        --transition: all 0.3s ease;
        --font-family: var(--bs-font-sans-serif, 'Inter', system-ui, -apple-system, sans-serif);
    }

    .notices-container {
        max-width: min(1200px, 100% - 2rem);
        margin: 1rem auto;
        padding: 0 1rem;
        width: 100%;
    }

    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .page-title {
        font-size: 1.75rem;
        font-weight: 700;
        margin: 0;
        color: var(--dark);
    }

    .page-subtitle {
        color: var(--gray);
        margin: 0.25rem 0 0;
        font-size: 1rem;
    }

    .hall-badge {
        background-color: var(--primary-light);
        color: var(--primary);
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.875rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .filter-container {
        margin-bottom: 1.5rem;
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        align-items: center;
        justify-content: space-between;
    }

    .filter-tabs {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .filter-tab {
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.875rem;
        font-weight: 500;
        background: var(--light);
        color: var(--gray);
        border: 1px solid rgba(0, 0, 0, 0.05);
        cursor: pointer;
        transition: var(--transition);
        text-decoration: none;
    }

    .filter-tab:hover {
        background: var(--primary-light);
        color: var(--primary);
    }

    .filter-tab.active {
        background: var(--primary);
        color: var(--white);
        border-color: var(--primary);
    }

    .search-box {
        min-width: 300px;
    }

    .search-box .input-group-text {
        border-radius: var(--border-radius) 0 0 var(--border-radius);
    }

    .search-box .form-control {
        border-radius: 0 var(--border-radius) var(--border-radius) 0;
    }

    .notices-list {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .notice-card {
        background: var(--white);
        border-radius: var(--border-radius);
        padding: 1.5rem;
        box-shadow: var(--box-shadow);
        border-left: 4px solid var(--info);
        transition: var(--transition);
    }

    .notice-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
    }

    .notice-card.urgent {
        border-left-color: var(--danger);
    }

    .notice-card.important {
        border-left-color: var(--warning);
    }

    .notice-card.normal {
        border-left-color: var(--info);
    }

    .notice-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 1rem;
    }

    .notice-title-section {
        flex: 1;
        margin-right: 1rem;
    }

    .notice-title {
        font-size: 1.25rem;
        font-weight: 600;
        margin: 0 0 0.5rem;
        color: var(--dark);
    }

    .notice-badges {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .notice-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .notice-badge.urgent {
        background-color: rgba(247, 37, 133, 0.1);
        color: var(--danger);
    }

    .notice-badge.important {
        background-color: rgba(248, 150, 30, 0.1);
        color: var(--warning);
    }

    .notice-badge.normal {
        background-color: rgba(72, 149, 239, 0.1);
        color: var(--info);
    }

    .notice-badge.new {
        background-color: rgba(76, 201, 240, 0.1);
        color: var(--success);
    }

    .notice-content {
        color: var(--dark);
        margin: 1rem 0;
        line-height: 1.6;
    }

    .notice-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid rgba(0, 0, 0, 0.05);
    }

    .notice-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 1.5rem;
    }

    .notice-meta-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: var(--gray);
        font-size: 0.875rem;
    }

    .notice-meta-item i {
        color: var(--primary);
    }

    .notice-actions {
        display: flex;
        gap: 0.5rem;
    }

    .notice-attachment {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid rgba(0, 0, 0, 0.05);
    }

    .attachment-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background: var(--light);
        padding: 0.5rem 0.75rem;
        border-radius: var(--border-radius);
        font-size: 0.75rem;
        color: var(--gray);
        transition: var(--transition);
    }

    .attachment-badge i {
        color: var(--primary);
    }

    .attachment-badge:hover {
        background: var(--primary-light);
        color: var(--primary);
    }

    .empty-state {
        text-align: center;
        padding: 3rem 1rem;
        background: var(--light);
        border-radius: var(--border-radius);
        margin: 2rem 0;
    }

    .empty-state-icon {
        font-size: 3rem;
        color: var(--gray);
        margin-bottom: 1rem;
    }

    .empty-state h4 {
        color: var(--dark);
        margin-bottom: 0.5rem;
    }

    .empty-state p {
        color: var(--gray);
        margin: 0;
    }

    @media (max-width: 991.98px) {
        .page-header {
            flex-direction: column;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .filter-container {
            flex-direction: column;
            align-items: stretch;
            gap: 0.75rem;
        }

        .filter-tabs {
            overflow-x: auto;
            white-space: nowrap;
            -webkit-overflow-scrolling: touch;
            padding-bottom: 0.5rem;
        }

        .search-box {
            min-width: 100%;
        }

        .notice-card {
            padding: 1rem;
        }

        .notice-header {
            flex-direction: column;
            gap: 0.75rem;
        }

        .notice-actions {
            width: 100%;
            justify-content: flex-end;
        }

        .notice-footer {
            flex-direction: column;
            gap: 1rem;
        }

        .notice-meta {
            flex-direction: column;
            gap: 0.75rem;
        }

        .notice-attachment {
            overflow-x: auto;
            white-space: nowrap;
            padding-bottom: 0.5rem;
        }

        .view-btn {
            width: 100%;
            justify-content: center;
        }
    }

    @media (max-width: 575.98px) {
        .notices-container {
            margin: 0.5rem auto;
            padding: 0 0.5rem;
        }

        .page-title {
            font-size: 1.5rem;
        }

        .notice-title {
            font-size: 1.1rem;
        }

        .notice-content {
            font-size: 0.9rem;
        }

        .notice-meta-item {
            font-size: 0.8rem;
        }
    }

    /* Notice View Modal Styles */
    .notice-view {
        padding: 1rem;
    }

    .notice-content {
        font-size: 1rem;
        line-height: 1.6;
        color: var(--dark);
    }

    .attachment-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-decoration: none;
        color: var(--dark);
        padding: 1rem;
        border: 1px solid rgba(0, 0, 0, 0.1);
        border-radius: var(--border-radius);
        transition: var(--transition);
        gap: 0.5rem;
        min-width: 120px;
        text-align: center;
    }

    .attachment-item:hover {
        background-color: var(--primary-light);
        border-color: var(--primary);
        color: var(--primary);
        transform: translateY(-2px);
    }

    .attachment-item.pdf {
        color: var(--danger);
    }

    .attachment-item.pdf:hover {
        background-color: rgba(247, 37, 133, 0.1);
        border-color: var(--danger);
    }

    .attachment-item span {
        font-size: 0.75rem;
        max-width: 100px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .attachment-preview {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        cursor: pointer;
        padding: 0.5rem;
        border: 1px solid rgba(0, 0, 0, 0.1);
        border-radius: var(--border-radius);
        transition: var(--transition);
    }

    .attachment-preview:hover {
        background-color: var(--primary-light);
        border-color: var(--primary);
        transform: translateY(-2px);
    }

    .attachment-preview img {
        border-radius: var(--border-radius);
        transition: var(--transition);
    }

    .attachment-preview:hover img {
        transform: scale(1.05);
    }

    /* Quill Editor Styles */
    .ql-editor {
        min-height: 200px;
        font-size: 1rem;
        line-height: 1.6;
    }

    .ql-toolbar {
        border-top-left-radius: var(--border-radius);
        border-top-right-radius: var(--border-radius);
        border-color: rgba(0, 0, 0, 0.1);
    }

    .ql-container {
        border-bottom-left-radius: var(--border-radius);
        border-bottom-right-radius: var(--border-radius);
        border-color: rgba(0, 0, 0, 0.1);
    }

    /* SweetAlert2 Custom Styles */
    .swal2-popup {
        border-radius: var(--border-radius);
        padding: 2rem;
    }

    .swal2-title {
        font-size: 1.5rem;
        font-weight: 600;
        color: var(--dark);
    }

    .swal2-html-container {
        font-size: 1rem;
        color: var(--gray);
    }

    .swal2-confirm {
        background-color: var(--primary) !important;
        border-radius: var(--border-radius) !important;
    }

    .swal2-cancel {
        background-color: var(--gray) !important;
        border-radius: var(--border-radius) !important;
    }
</style>