# Generated by Django 3.2.7 on 2021-09-18 11:10

from django.db import migrations, models


class Migration(migrations.Migration):

    dependencies = [
        ('main', '0004_auto_20210918_1408'),
    ]

    operations = [
        migrations.AlterField(
            model_name='course',
            name='points',
            field=models.DecimalField(decimal_places=1, max_digits=3),
        ),
    ]
