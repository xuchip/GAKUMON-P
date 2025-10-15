    </div>

    <?php if(isset($pageJS)): ?>
      <script src="<?= htmlspecialchars($pageJS) ?>"></script>
    <?php endif; ?>
    <?php if(isset($pageJS2)): ?>
      <script src="<?= htmlspecialchars($pageJS2) ?>"></script>
    <?php endif; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" integrity="sha384-ndDqU0Gzau9qJ1lfW4pNLlhNTkCfHzAVBReH9diLvGRem5+R9g2FzA8ZGN954O5Q" crossorigin="anonymous"></script>
  </body>
</html>