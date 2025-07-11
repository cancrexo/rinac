<!-- 
Plantilla base para modales de RINAC
Variables disponibles: $modal_id, $title, $content, $footer, $size
-->

<div id="<?php echo esc_attr($modal_id); ?>" class="rinac-modal <?php echo esc_attr($size ?? 'medium'); ?>">
    <div class="rinac-modal-overlay"></div>
    <div class="rinac-modal-container">
        <div class="rinac-modal-content">
            <!-- Header -->
            <div class="rinac-modal-header">
                <h3 class="rinac-modal-title"><?php echo esc_html($title); ?></h3>
                <button type="button" class="rinac-modal-close" aria-label="<?php _e('Cerrar', 'rinac'); ?>">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <!-- Body -->
            <div class="rinac-modal-body">
                <?php echo $content; ?>
            </div>

            <!-- Footer (opcional) -->
            <?php if (!empty($footer)): ?>
            <div class="rinac-modal-footer">
                <?php echo $footer; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.rinac-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 999999;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
}

.rinac-modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(2px);
}

.rinac-modal-container {
    position: relative;
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.rinac-modal-content {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    max-height: 90vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    position: relative;
    animation: modalSlideIn 0.3s ease-out;
}

.rinac-modal.small .rinac-modal-content { max-width: 400px; width: 100%; }
.rinac-modal.medium .rinac-modal-content { max-width: 600px; width: 100%; }
.rinac-modal.large .rinac-modal-content { max-width: 800px; width: 100%; }
.rinac-modal.extra-large .rinac-modal-content { max-width: 1200px; width: 100%; }

.rinac-modal-header {
    padding: 20px 20px 0;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    border-bottom: 1px solid #eee;
    margin-bottom: 20px;
}

.rinac-modal-title {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
    color: #333;
    line-height: 1.4;
}

.rinac-modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #666;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
    transition: all 0.2s ease;
}

.rinac-modal-close:hover {
    background: #f5f5f5;
    color: #333;
}

.rinac-modal-body {
    padding: 0 20px;
    overflow-y: auto;
    flex: 1;
}

.rinac-modal-footer {
    padding: 20px;
    border-top: 1px solid #eee;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 20px;
}

@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: scale(0.9) translateY(-50px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

/* Responsive */
@media (max-width: 768px) {
    .rinac-modal-container {
        padding: 10px;
    }
    
    .rinac-modal-content {
        margin: 0;
        max-height: 95vh;
    }
    
    .rinac-modal.medium .rinac-modal-content,
    .rinac-modal.large .rinac-modal-content,
    .rinac-modal.extra-large .rinac-modal-content {
        max-width: 100%;
    }
    
    .rinac-modal-header,
    .rinac-modal-body,
    .rinac-modal-footer {
        padding-left: 15px;
        padding-right: 15px;
    }
}
</style>
