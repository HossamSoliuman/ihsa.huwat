        </main>
    </div>
</div>

<script src="<?= e(assetUrl('js/app.js')) ?>"></script>
<?php foreach (($pageScripts ?? []) as $pageScript): ?>
<script src="<?= e(assetUrl((string)$pageScript)) ?>"></script>
<?php endforeach; ?>
</body>
</html>
