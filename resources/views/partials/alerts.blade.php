@if(session('success'))
    <div class="alert alert-success">
        <i class="fa fa-check-circle"></i> {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger">
        <i class="fa fa-exclamation-triangle"></i> {{ session('error') }}
    </div>
@endif

@if(session('warning'))
    <div class="alert alert-warning">
        <i class="fa fa-info-circle"></i> {{ session('warning') }}
    </div>
@endif

@if(isset($errors) && $errors->any())
    <div class="alert alert-danger">
        <strong>Atenção:</strong> Por favor, verifique os erros abaixo:
        <ul style="margin: 5px 0 0 20px; padding: 0;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
